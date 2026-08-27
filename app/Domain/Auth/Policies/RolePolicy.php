<?php

declare(strict_types=1);

namespace App\Domain\Auth\Policies;

use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;

class RolePolicy
{
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
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->can('roles.update')) {
            return false;
        }

        // Não pode editar role com nível superior ao seu próprio maior nível
        if ($role->level > $user->highestRoleLevel()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Role $role): bool
    {
        // Impede a exclusão das roles estruturais essenciais
        if (in_array($role->slug, ['admin', 'super-admin'], true)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->can('roles.delete')) {
            return false;
        }

        // Não pode excluir role com nível superior ao seu próprio
        if ($role->level > $user->highestRoleLevel()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Role $role): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->can('roles.delete')) {
            return false;
        }

        return $role->level <= $user->highestRoleLevel();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Role $role): bool
    {
        return $this->delete($user, $role);
    }
}
