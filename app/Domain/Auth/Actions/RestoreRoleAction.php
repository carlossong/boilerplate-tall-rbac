<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Models\Role;

final readonly class RestoreRoleAction
{
    public function __invoke(Role $role): bool
    {
        return (bool) $role->restore();
    }
}
