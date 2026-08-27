<?php

declare(strict_types=1);

namespace App\Domain\Auth\Models;

use App\Domain\Auth\Enums\RoleLevel;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property int $level
 * @property bool $is_system
 * @property string|null $description
 * @property Collection<int, Permission> $permissions
 */
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    public const int LEVEL_SUPER_ADMIN = 100;

    public const int LEVEL_ADMIN = 80;

    public const int LEVEL_MANAGER = 50;

    public const int LEVEL_SUPERVISOR = 30;

    public const int LEVEL_OPERATOR = 20;

    public const int LEVEL_VIEWER = 10;

    /**
     * @var Collection<string, Role>|null
     */
    protected static ?Collection $cachedRoles = null;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    /**
     * Get all active roles with their permissions memoized in-memory.
     *
     * @return Collection<string, Role>
     */
    public static function getCachedRoles(): Collection
    {
        if (static::$cachedRoles === null) {
            /** @var Collection<string, Role> $roles */
            $roles = static::with('permissions')
                ->get()
                ->keyBy('id');

            static::$cachedRoles = $roles;
        }

        return static::$cachedRoles;
    }

    /**
     * Flush the in-memory memoized roles.
     */
    public static function flushCache(): void
    {
        static::$cachedRoles = null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_system' => 'boolean',
        ];
    }

    /**
     * Determine if the role is an immutable system role.
     */
    public function isSystem(): bool
    {
        return (bool) ($this->is_system ?? false) || in_array($this->slug, ['admin', 'super-admin'], true);
    }

    /**
     * Determine if this role grants administrative powers.
     */
    public function isAdministrative(): bool
    {
        return $this->isSystem() || $this->level >= self::LEVEL_MANAGER;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): RoleFactory
    {
        return RoleFactory::new();
    }

    /**
     * Get the strongly typed RoleLevel enum if matching a canonical tier.
     */
    public function levelEnum(): ?RoleLevel
    {
        return RoleLevel::tryFrom($this->level);
    }

    /**
     * Determine the badge color according to level hierarchy.
     */
    public function levelBadgeColor(): string
    {
        return $this->levelEnum()?->color() ?? match (true) {
            $this->level >= 80 => 'emerald',
            $this->level >= 50 => 'indigo',
            $this->level >= 20 => 'amber',
            default => 'zinc',
        };
    }

    /**
     * @param  Builder<Role>  $query
     * @param  'asc'|'desc'  $direction
     * @return Builder<Role>
     */
    public function scopeOrderByLevel(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('level', $direction);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Department, $this>
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_role')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
            ->withTimestamps();
    }
}
