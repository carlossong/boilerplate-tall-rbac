<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTOs\RoleData;
use App\Domain\Auth\Models\Role;
use Illuminate\Support\Facades\DB;

final readonly class CreateRoleAction
{
    public function __invoke(RoleData $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data->name,
                'slug' => $data->slug,
                'level' => $data->level,
                'description' => $data->description,
            ]);

            if (! empty($data->permissionIds)) {
                $role->permissions()->sync($data->permissionIds);
            }

            if (! empty($data->departmentIds)) {
                $role->departments()->sync($data->departmentIds);
            }

            return $role;
        });
    }
}
