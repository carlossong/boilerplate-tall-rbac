<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Models\User;
use DomainException;

final readonly class DeleteUserAction
{
    public function __invoke(User $user, ?User $authenticatedUser = null): bool
    {
        if ($authenticatedUser !== null && $authenticatedUser->id === $user->id) {
            throw new DomainException(__('You cannot delete your own account.'));
        }

        if ($user->is_super_admin) {
            $otherActiveSuperAdmins = User::withoutTrashed()
                ->where('is_super_admin', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherActiveSuperAdmins === 0) {
                throw new DomainException(__('Cannot delete the last remaining active super administrator.'));
            }
        }

        return (bool) $user->delete();
    }
}
