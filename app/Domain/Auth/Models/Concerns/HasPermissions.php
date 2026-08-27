<?php

declare(strict_types=1);

namespace App\Domain\Auth\Models\Concerns;

trait HasPermissions
{
    /**
     * @var array<string>|null
     */
    protected ?array $cachedPermissionSlugs = null;

    /**
     * Determine if the user has the given role(s).
     *
     * @param  string|array<string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (is_string($roles)) {
            $roles = [$roles];
        }

        if (! $this->relationLoaded('roles')) {
            $this->loadMissing('roles');
        }

        return $this->roles->pluck('slug')->intersect($roles)->isNotEmpty();
    }

    /**
     * Determine if the user has the given permission.
     */
    public function hasPermissionTo(string $permissionSlug): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->cachedPermissionSlugs === null) {
            if (! $this->relationLoaded('roles')) {
                $this->loadMissing('roles.permissions');
            } else {
                $this->roles->loadMissing('permissions');
            }

            $this->cachedPermissionSlugs = $this->roles
                ->flatMap(fn ($role) => $role->permissions)
                ->pluck('slug')
                ->unique()
                ->all();
        }

        return in_array($permissionSlug, $this->cachedPermissionSlugs, true);
    }

    /**
     * Check if the user is a super administrator.
     */
    public function isSuperAdmin(): bool
    {
        return (bool) ($this->is_super_admin ?? false);
    }

    /**
     * Clear cached permission slugs for the instance.
     */
    public function flushCachedPermissions(): void
    {
        $this->cachedPermissionSlugs = null;
    }
}
