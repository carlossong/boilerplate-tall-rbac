<?php

declare(strict_types=1);

namespace App\Domain\Auth\Policies;

use App\Domain\Auth\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $user->can('users.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->can('users.update')) {
            return false;
        }

        // Allows editing own profile if user has users.update permission
        if ($user->id === $model->id) {
            return true;
        }

        return $this->canManageUserHierarchy($user, $model);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // A user cannot delete their own account
        if ($user->id === $model->id) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->can('users.delete')) {
            return false;
        }

        return $this->canManageUserHierarchy($user, $model);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->can('users.delete')) {
            return false;
        }

        return $this->canManageUserHierarchy($user, $model);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $this->delete($user, $model);
    }

    /**
     * Check if the actor has strictly higher hierarchical authority over the target user.
     */
    private function canManageUserHierarchy(User $user, User $target): bool
    {
        if ($target->isSuperAdmin()) {
            return false;
        }

        return $user->highestRoleLevel() > $target->highestRoleLevel();
    }
}
