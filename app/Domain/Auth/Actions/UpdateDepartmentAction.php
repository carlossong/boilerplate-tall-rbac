<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTOs\DepartmentData;
use App\Domain\Auth\Models\Department;
use Illuminate\Support\Facades\DB;

final readonly class UpdateDepartmentAction
{
    public function __invoke(Department $department, DepartmentData $data): Department
    {
        return DB::transaction(function () use ($department, $data) {
            $department->update([
                'name' => $data->name,
                'slug' => $data->slug,
                'description' => $data->description,
                'is_active' => $data->isActive,
            ]);

            $department->roles()->sync($data->roleIds);

            return $department;
        });
    }
}
