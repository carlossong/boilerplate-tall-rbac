<?php

declare(strict_types=1);

namespace App\Domain\Auth\Enums;

enum PermissionAuditAction: string
{
    case Assigned = 'assigned';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => __('Assigned'),
            self::Revoked => __('Revoked'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Assigned => 'emerald',
            self::Revoked => 'red',
        };
    }
}
