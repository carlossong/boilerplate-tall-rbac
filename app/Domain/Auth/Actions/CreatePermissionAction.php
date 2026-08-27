<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTOs\PermissionData;
use App\Domain\Auth\Models\Permission;

final readonly class CreatePermissionAction
{
    public function __invoke(PermissionData $data): Permission
    {
        $permission = Permission::create([
            'name' => $data->name,
            'slug' => $data->slug,
            'description' => $data->description,
            'group' => $data->group,
        ]);

        return $permission;
    }
}
