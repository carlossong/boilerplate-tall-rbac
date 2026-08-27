<?php

declare(strict_types=1);

namespace App\Domain\Auth\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\Auth\Policies\Concerns\ChecksRecordAccess;

class UserPolicy
{
    use ChecksRecordAccess;

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
        return $this->allowsAbilityThenRecord($user, 'users.update', function (User $actor) use ($model): bool {
            if ($actor->id === $model->id) {
                return true;
            }

            return $this->canManageUserHierarchy($actor, $model);
        });
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        if ($model->isSuperAdmin()) {
            $otherActiveSuperAdmins = User::withoutTrashed()
                ->where('is_super_admin', true)
                ->where('id', '!=', $model->id)
                ->count();

            if ($otherActiveSuperAdmins === 0) {
                return false;
            }
        }

        return $this->allowsAbilityThenRecord(
            $user,
            'users.delete',
            fn (User $actor): bool => $this->canManageUserHierarchy($actor, $model),
        );
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $this->allowsAbilityThenRecord(
            $user,
            'users.delete',
            fn (User $actor): bool => $this->canManageUserHierarchy($actor, $model),
        );
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
