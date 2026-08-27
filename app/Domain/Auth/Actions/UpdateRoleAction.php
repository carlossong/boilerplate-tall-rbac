<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTOs\RoleData;
use App\Domain\Auth\Models\Role;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class UpdateRoleAction
{
    public function __invoke(Role $role, RoleData $data): Role
    {
        if ($role->isSystem()) {
            throw new DomainException(__('System roles cannot be edited.'));
        }

        return DB::transaction(function () use ($role, $data) {
            $role->update([
                'name' => $data->name,
                'slug' => $data->slug,
                'level' => $data->level,
                'description' => $data->description,
            ]);

            $role->departments()->sync($data->departmentIds);

            return $role;
        });
    }
}
