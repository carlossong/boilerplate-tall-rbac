<?php

declare(strict_types=1);

namespace App\Domain\Auth\Observers;

use App\Domain\Auth\Enums\PermissionAuditAction;
use App\Domain\Auth\Models\PermissionAuditLog;
use App\Domain\Auth\Models\Pivots\PermissionUser;
use App\Domain\Auth\Support\AccessCache;

class PermissionUserObserver
{
    public function created(PermissionUser $pivot): void
    {
        AccessCache::forgetUser($pivot->user_id);

        PermissionAuditLog::record(
            PermissionAuditAction::Assigned,
            PermissionAuditLog::SUBJECT_USER,
            $pivot->user_id,
            PermissionAuditLog::GRANTABLE_PERMISSION,
            $pivot->permission_id,
        );
    }

    public function deleted(PermissionUser $pivot): void
    {
        AccessCache::forgetUser($pivot->user_id);

        PermissionAuditLog::record(
            PermissionAuditAction::Revoked,
            PermissionAuditLog::SUBJECT_USER,
            $pivot->user_id,
            PermissionAuditLog::GRANTABLE_PERMISSION,
            $pivot->permission_id,
        );
    }
}
