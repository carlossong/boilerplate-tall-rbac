<?php

declare(strict_types=1);

namespace App\Domain\Auth\Models\Pivots;

use App\Domain\Auth\Observers\RoleUserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $user_id
 * @property string $role_id
 */
#[ObservedBy(RoleUserObserver::class)]
class RoleUser extends Pivot
{
    protected $table = 'role_user';

    public $incrementing = false;

    protected $guarded = [];
}
