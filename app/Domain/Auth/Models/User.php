<?php

declare(strict_types=1);

namespace App\Domain\Auth\Models;

use App\Domain\Auth\Models\Concerns\HasPermissions;
use App\Domain\Auth\Models\Pivots\DepartmentUser;
use App\Domain\Auth\Models\Pivots\RoleUser;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property bool $is_super_admin
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property Collection<int, Role> $roles
 * @property Collection<int, Department> $departments
 */
#[Fillable(['name', 'email', 'password', 'is_super_admin'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory,
        HasPermissions,
        HasUuids,
        Notifiable,
        PasskeyAuthenticatable,
        SoftDeletes,
        TwoFactorAuthenticatable;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * @return BelongsToMany<Role, $this, RoleUser>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->using(RoleUser::class)
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Department, $this, DepartmentUser>
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_user')
            ->using(DepartmentUser::class)
            ->withPivot(['id', 'role_id', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Synchronize the user's departmental assignments with pivot metadata.
     *
     * @param  array<int|string, array<string, mixed>|string>  $assignments
     */
    public function syncDepartments(array $assignments): void
    {
        $syncData = [];
        foreach ($assignments as $item) {
            if (is_string($item)) {
                $syncData[$item] = [
                    'id' => (string) Str::uuid(),
                    'role_id' => null,
                    'is_primary' => empty($syncData),
                ];
            } elseif (isset($item['department_id'])) {
                $syncData[$item['department_id']] = [
                    'id' => (string) Str::uuid(),
                    'role_id' => ! empty($item['role_id']) ? $item['role_id'] : null,
                    'is_primary' => (bool) ($item['is_primary'] ?? false),
                ];
            }
        }

        $this->departments()->sync($syncData);
        $this->flushCachedPermissions();
    }
}
