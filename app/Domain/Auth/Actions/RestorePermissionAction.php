<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Models\Permission;

final readonly class RestorePermissionAction
{
    public function __invoke(Permission $permission): bool
    {
        return (bool) $permission->restore();
    }
}
