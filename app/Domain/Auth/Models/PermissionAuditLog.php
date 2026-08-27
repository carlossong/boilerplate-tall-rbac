<?php

declare(strict_types=1);

namespace App\Domain\Auth\Models;

use App\Domain\Auth\Enums\PermissionAuditAction;
use Database\Factories\PermissionAuditLogFactory;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property PermissionAuditAction $action
 * @property string|null $actor_id
 * @property string|null $actor_name
 * @property string|null $actor_email
 * @property string $subject_type
 * @property string $subject_id
 * @property string $subject_name
 * @property string $grantable_type
 * @property string $grantable_id
 * @property string $grantable_name
 * @property string|null $department_id
 * @property string|null $department_name
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $created_at
 */
class PermissionAuditLog extends Model
{
    /** @use HasFactory<PermissionAuditLogFactory> */
    use HasFactory, HasUuids;

    public const string SUBJECT_USER = 'user';

    public const string SUBJECT_ROLE = 'role';

    public const string GRANTABLE_ROLE = 'role';

    public const string GRANTABLE_PERMISSION = 'permission';

    public const UPDATED_AT = null;

    protected $guarded = [];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PermissionAuditLogFactory
    {
        return PermissionAuditLogFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => PermissionAuditAction::class,
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new DomainException(__('Permission audit logs cannot be modified.'));
        });

        static::deleting(function (): never {
            throw new DomainException(__('Permission audit logs cannot be deleted.'));
        });
    }

    /**
     * Persist an access-change event captured from a pivot observer.
     */
    public static function record(
        PermissionAuditAction $action,
        string $subjectType,
        string $subjectId,
        string $grantableType,
        string $grantableId,
        ?string $departmentId = null,
    ): self {
        $authUser = auth()->user();
        $actor = $authUser instanceof User ? $authUser : null;

        return static::query()->create([
            'action' => $action,
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'actor_email' => $actor?->email,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'subject_name' => self::resolveName($subjectType, $subjectId),
            'grantable_type' => $grantableType,
            'grantable_id' => $grantableId,
            'grantable_name' => self::resolveName($grantableType, $grantableId),
            'department_id' => $departmentId,
            'department_name' => $departmentId !== null
                ? self::resolveName('department', $departmentId)
                : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @param  Builder<PermissionAuditLog>  $query
     * @return Builder<PermissionAuditLog>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%'.$term.'%';

        return $query->where(function (Builder $nested) use ($like): void {
            $nested->where('actor_name', 'like', $like)
                ->orWhere('actor_email', 'like', $like)
                ->orWhere('subject_name', 'like', $like)
                ->orWhere('grantable_name', 'like', $like)
                ->orWhere('department_name', 'like', $like);
        });
    }

    public function grantableKindLabel(): string
    {
        return match ($this->grantable_type) {
            self::GRANTABLE_PERMISSION => __('Permission'),
            default => __('Role'),
        };
    }

    public function subjectKindLabel(): string
    {
        return match ($this->subject_type) {
            self::SUBJECT_ROLE => __('Role'),
            default => __('User'),
        };
    }

    private static function resolveName(string $type, string $id): string
    {
        $model = match ($type) {
            'user' => User::withTrashed()->find($id),
            'role' => Role::withTrashed()->find($id),
            'permission' => Permission::withTrashed()->find($id),
            'department' => Department::withTrashed()->find($id),
            default => null,
        };

        if ($model instanceof User) {
            return $model->name;
        }

        if ($model instanceof Permission) {
            return $model->slug;
        }

        if ($model instanceof Role || $model instanceof Department) {
            return $model->name;
        }

        return $id;
    }
}
