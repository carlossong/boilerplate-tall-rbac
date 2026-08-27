<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Models\User;

final readonly class RestoreUserAction
{
    public function __invoke(User $user): bool
    {
        return (bool) $user->restore();
    }
}
