<?php

declare(strict_types=1);

namespace App\Domain\Auth\Models;

use App\Domain\Auth\Models\Pivots\PermissionRole;
use App\Domain\Auth\Support\AccessCache;
use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    /** @use HasFactory<PermissionFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $guarded = [];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PermissionFactory
    {
        return PermissionFactory::new();
    }

    protected static function booted(): void
    {
        static::saved(function () {
            AccessCache::forgetPermissions();
        });

        static::deleted(function () {
            AccessCache::forgetPermissions();
        });

        static::restored(function () {
            AccessCache::forgetPermissions();
        });
    }

    /**
     * Flush cached permission catalogs and user permission sets.
     */
    public static function flushCache(): void
    {
        AccessCache::forgetPermissions();
    }

    /**
     * @return BelongsToMany<Role, $this, PermissionRole>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role')
            ->using(PermissionRole::class)
            ->withTimestamps();
    }
}
