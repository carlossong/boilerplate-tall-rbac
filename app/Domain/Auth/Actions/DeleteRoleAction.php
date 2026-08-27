<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Models\Role;
use DomainException;

final readonly class DeleteRoleAction
{
    public function __invoke(Role $role): bool
    {
        if ($role->isSystem()) {
            throw new DomainException(__('System roles cannot be deleted.'));
        }

        return (bool) $role->delete();
    }
}
