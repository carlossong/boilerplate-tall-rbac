<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTOs\DepartmentData;
use App\Domain\Auth\Models\Department;
use Illuminate\Support\Facades\DB;

final readonly class CreateDepartmentAction
{
    public function __invoke(DepartmentData $data): Department
    {
        return DB::transaction(function () use ($data) {
            $department = Department::create([
                'name' => $data->name,
                'slug' => $data->slug,
                'description' => $data->description,
                'is_active' => $data->isActive,
            ]);

            if (! empty($data->roleIds)) {
                $department->roles()->sync($data->roleIds);
            }

            return $department;
        });
    }
}
