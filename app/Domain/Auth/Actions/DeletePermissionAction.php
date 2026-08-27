<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Models\Permission;

final readonly class DeletePermissionAction
{
    public function __invoke(Permission $permission): bool
    {
        $deleted = (bool) $permission->delete();
        Permission::flushCache();

        return $deleted;
    }
}
