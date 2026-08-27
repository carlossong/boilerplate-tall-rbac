<?php

declare(strict_types=1);

namespace App\Domain\Auth\Policies;

use App\Domain\Auth\Models\PermissionAuditLog;
use App\Domain\Auth\Models\User;

class PermissionAuditLogPolicy
{
    /**
     * Determine whether the user can view the audit trail.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('audit-logs.view');
    }

    /**
     * Determine whether the user can view a single audit entry.
     */
    public function view(User $user, PermissionAuditLog $permissionAuditLog): bool
    {
        return $user->can('audit-logs.view');
    }
}
