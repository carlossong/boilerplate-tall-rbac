<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Models\Department;

final readonly class DeleteDepartmentAction
{
    public function __invoke(Department $department): bool
    {
        return (bool) $department->delete();
    }
}
