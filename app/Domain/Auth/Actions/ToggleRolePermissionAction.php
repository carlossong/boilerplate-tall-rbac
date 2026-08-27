<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use DomainException;

final readonly class ToggleRolePermissionAction
{
    /**
     * Grant or revoke a single permission on a role. Returns true when the permission is now granted.
     */
    public function __invoke(Role $role, Permission $permission): bool
    {
        if ($role->isSystem()) {
            throw new DomainException(__('System roles cannot be edited.'));
        }

        $alreadyGranted = $role->permissions()->where('permissions.id', $permission->id)->exists();

        if ($alreadyGranted) {
            $role->permissions()->detach($permission->id);

            return false;
        }

        $role->permissions()->attach($permission->id);

        return true;
    }
}
