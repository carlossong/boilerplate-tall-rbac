<?php

declare(strict_types=1);

namespace App\Domain\Auth\Policies;

use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Policies\Concerns\ChecksRecordAccess;

class RolePolicy
{
    use ChecksRecordAccess;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Role $role): bool
    {
        if ($role->isSystem()) {
            return false;
        }

        return $this->allowsAbilityThenRecord(
            $user,
            'roles.update',
            fn (User $actor): bool => $role->level <= $actor->highestRoleLevel(),
        );
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Role $role): bool
    {
        if ($role->isSystem()) {
            return false;
        }

        return $this->allowsAbilityThenRecord(
            $user,
            'roles.delete',
            fn (User $actor): bool => $role->level <= $actor->highestRoleLevel(),
        );
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Role $role): bool
    {
        return $this->allowsAbilityThenRecord(
            $user,
            'roles.delete',
            fn (User $actor): bool => $role->level <= $actor->highestRoleLevel(),
        );
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Role $role): bool
    {
        return $this->delete($user, $role);
    }
}
