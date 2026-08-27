<?php

declare(strict_types=1);

namespace App\Domain\Auth\Enums;

enum RoleLevel: int
{
    case SUPER_ADMIN = 100;
    case ADMIN = 80;
    case MANAGER = 50;
    case SUPERVISOR = 30;
    case OPERATOR = 20;
    case VIEWER = 10;

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => __('Super Administrator (100)'),
            self::ADMIN => __('Administrator (80)'),
            self::MANAGER => __('Department Manager (50)'),
            self::SUPERVISOR => __('Sector Supervisor (30)'),
            self::OPERATOR => __('Operator (20)'),
            self::VIEWER => __('Viewer (10)'),
        };
    }

    public function color(): string
    {
        return match (true) {
            $this->value >= 80 => 'emerald',
            $this->value >= 50 => 'indigo',
            $this->value >= 20 => 'amber',
            default => 'zinc',
        };
    }
}
