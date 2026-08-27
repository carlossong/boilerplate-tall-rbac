<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTOs\PermissionData;
use App\Domain\Auth\Models\Permission;

final readonly class UpdatePermissionAction
{
    public function __invoke(Permission $permission, PermissionData $data): Permission
    {
        $permission->update([
            'name' => $data->name,
            'slug' => $data->slug,
            'description' => $data->description,
            'group' => $data->group,
        ]);

        return $permission;
    }
}
