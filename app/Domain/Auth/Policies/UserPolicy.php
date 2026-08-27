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

        // Não pode atualizar um Super Admin se o autor não for Super Admin
        if ($model->isSuperAdmin()) {
            return false;
        }

        // Se estiver editando outro usuário, exige nível hierárquico estritamente superior
        if ($user->id !== $model->id && $user->highestRoleLevel() <= $model->highestRoleLevel()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Um usuário não pode excluir a si mesmo
        if ($user->id === $model->id) {
            return false;
        }

        // Super Admin não pode ser excluído por usuário comum
        if ($model->isSuperAdmin()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->can('users.delete')) {
            return false;
        }

        // Exige nível hierárquico estritamente superior para excluir outro usuário
        return $user->highestRoleLevel() > $model->highestRoleLevel();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        if ($model->isSuperAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->can('users.delete')) {
            return false;
        }

        return $user->highestRoleLevel() > $model->highestRoleLevel();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $this->delete($user, $model);
    }
}
