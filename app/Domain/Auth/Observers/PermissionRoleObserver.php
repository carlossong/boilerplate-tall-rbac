<?php

declare(strict_types=1);

namespace App\Domain\Auth\Observers;

use App\Domain\Auth\Enums\PermissionAuditAction;
use App\Domain\Auth\Models\PermissionAuditLog;
use App\Domain\Auth\Models\Pivots\PermissionRole;

class PermissionRoleObserver
{
    public function created(PermissionRole $pivot): void
    {
        PermissionAuditLog::record(
            PermissionAuditAction::Assigned,
            PermissionAuditLog::SUBJECT_ROLE,
            $pivot->role_id,
            PermissionAuditLog::GRANTABLE_PERMISSION,
            $pivot->permission_id,
        );
    }

    public function deleted(PermissionRole $pivot): void
    {
        PermissionAuditLog::record(
            PermissionAuditAction::Revoked,
            PermissionAuditLog::SUBJECT_ROLE,
            $pivot->role_id,
            PermissionAuditLog::GRANTABLE_PERMISSION,
            $pivot->permission_id,
        );
    }
}
