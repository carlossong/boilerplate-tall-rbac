<?php

declare(strict_types=1);

namespace App\Domain\Auth\Models\Pivots;

use App\Domain\Auth\Observers\DepartmentUserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $id
 * @property string $department_id
 * @property string $user_id
 * @property string|null $role_id
 * @property bool $is_primary
 */
#[ObservedBy(DepartmentUserObserver::class)]
class DepartmentUser extends Pivot
{
    use HasUuids;

    protected $table = 'department_user';

    public $incrementing = false;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }
}
