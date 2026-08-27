<?php

declare(strict_types=1);

namespace App\Domain\Auth\Models\Pivots;

use App\Domain\Auth\Observers\PermissionUserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $user_id
 * @property string $permission_id
 */
#[ObservedBy(PermissionUserObserver::class)]
class PermissionUser extends Pivot
{
    protected $table = 'permission_user';

    public $incrementing = false;

    protected $guarded = [];
}
