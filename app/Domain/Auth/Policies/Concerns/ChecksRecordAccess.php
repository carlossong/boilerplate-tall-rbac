<?php

declare(strict_types=1);

namespace App\Domain\Auth\Policies\Concerns;

use App\Domain\Auth\Models\User;

trait ChecksRecordAccess
{
    /**
     * Global abilities are resolved by Gate / hasPermissionTo.
     * Record rules (ownership, hierarchy, portfolio) stay in the Policy method that receives the model.
     *
     * @param  callable(User): bool  $forRecord
     */
    protected function allowsAbilityThenRecord(User $user, string $ability, callable $forRecord): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermissionTo($ability)) {
            return false;
        }

        return $forRecord($user);
    }
}
