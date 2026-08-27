<?php

declare(strict_types=1);

namespace App\Domain\Auth\Models\Pivots;

use App\Domain\Auth\Observers\PermissionRoleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $role_id
 * @property string $permission_id
 */
#[ObservedBy(PermissionRoleObserver::class)]
class PermissionRole extends Pivot
{
    protected $table = 'permission_role';

    public $incrementing = false;

    protected $guarded = [];
}
