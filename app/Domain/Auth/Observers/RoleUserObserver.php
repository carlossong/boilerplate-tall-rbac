<?php

declare(strict_types=1);

namespace App\Domain\Auth\Observers;

use App\Domain\Auth\Enums\PermissionAuditAction;
use App\Domain\Auth\Models\PermissionAuditLog;
use App\Domain\Auth\Models\Pivots\RoleUser;

class RoleUserObserver
{
    public function created(RoleUser $pivot): void
    {
        PermissionAuditLog::record(
            PermissionAuditAction::Assigned,
            PermissionAuditLog::SUBJECT_USER,
            $pivot->user_id,
            PermissionAuditLog::GRANTABLE_ROLE,
            $pivot->role_id,
        );
    }

    public function deleted(RoleUser $pivot): void
    {
        PermissionAuditLog::record(
            PermissionAuditAction::Revoked,
            PermissionAuditLog::SUBJECT_USER,
            $pivot->user_id,
            PermissionAuditLog::GRANTABLE_ROLE,
            $pivot->role_id,
        );
    }
}
