<?php

declare(strict_types=1);

namespace App\Domain\Auth\Observers;

use App\Domain\Auth\Enums\PermissionAuditAction;
use App\Domain\Auth\Models\PermissionAuditLog;
use App\Domain\Auth\Models\Pivots\DepartmentUser;
use App\Domain\Auth\Support\AccessCache;

class DepartmentUserObserver
{
    public function created(DepartmentUser $pivot): void
    {
        if (blank($pivot->role_id)) {
            return;
        }

        AccessCache::forgetUser($pivot->user_id);

        PermissionAuditLog::record(
            PermissionAuditAction::Assigned,
            PermissionAuditLog::SUBJECT_USER,
            $pivot->user_id,
            PermissionAuditLog::GRANTABLE_ROLE,
            $pivot->role_id,
            $pivot->department_id,
        );
    }

    public function updated(DepartmentUser $pivot): void
    {
        if (! $pivot->wasChanged('role_id')) {
            return;
        }

        AccessCache::forgetUser($pivot->user_id);

        $previousRoleId = $pivot->getOriginal('role_id');
        $currentRoleId = $pivot->role_id;

        if (filled($previousRoleId)) {
            PermissionAuditLog::record(
                PermissionAuditAction::Revoked,
                PermissionAuditLog::SUBJECT_USER,
                $pivot->user_id,
                PermissionAuditLog::GRANTABLE_ROLE,
                (string) $previousRoleId,
                $pivot->department_id,
            );
        }

        if (filled($currentRoleId)) {
            PermissionAuditLog::record(
                PermissionAuditAction::Assigned,
                PermissionAuditLog::SUBJECT_USER,
                $pivot->user_id,
                PermissionAuditLog::GRANTABLE_ROLE,
                $currentRoleId,
                $pivot->department_id,
            );
        }
    }

    public function deleted(DepartmentUser $pivot): void
    {
        if (blank($pivot->role_id)) {
            return;
        }

        AccessCache::forgetUser($pivot->user_id);

        PermissionAuditLog::record(
            PermissionAuditAction::Revoked,
            PermissionAuditLog::SUBJECT_USER,
            $pivot->user_id,
            PermissionAuditLog::GRANTABLE_ROLE,
            $pivot->role_id,
            $pivot->department_id,
        );
    }
}
