<?php

declare(strict_types=1);

namespace App\Domain\Auth\Models\Concerns;

use App\Domain\Auth\Models\Department;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Support\AccessCache;

trait HasPermissions
{
    protected ?int $cachedHighestRoleLevel = null;

    /**
     * Determine if the user has the given role(s), optionally scoped to a department.
     *
     * @param  string|array<string>  $roles
     */
    public function hasRole(string|array $roles, Department|string|null $department = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($department !== null) {
            return $this->hasRoleInDepartment($roles, $department);
        }

        $rolesList = is_string($roles) ? [$roles] : $roles;

        if (! $this->relationLoaded('roles')) {
            $this->loadMissing('roles');
        }

        if ($this->roles->pluck('slug')->intersect($rolesList)->isNotEmpty()) {
            return true;
        }

        // Also check if any departmental assigned role matches
        if (! $this->relationLoaded('departments')) {
            $this->loadMissing('departments');
        }

        $catalog = AccessCache::roleCatalog();

        foreach ($this->departments as $dept) {
            $roleId = $dept->pivot->role_id ?? null;
            if (! filled($roleId) || ! isset($catalog[$roleId])) {
                continue;
            }

            if (in_array($catalog[$roleId]['slug'], $rolesList, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the user has a specific role inside a department.
     *
     * @param  string|array<string>  $roles
     */
    public function hasRoleInDepartment(string|array $roles, Department|string $department): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $rolesList = is_string($roles) ? [$roles] : $roles;

        // Global roles apply across all departments
        if (! $this->relationLoaded('roles')) {
            $this->loadMissing('roles');
        }

        if ($this->roles->pluck('slug')->intersect($rolesList)->isNotEmpty()) {
            return true;
        }

        $departmentId = $department instanceof Department ? $department->id : $department;

        if (! $this->relationLoaded('departments')) {
            $this->loadMissing('departments');
        }

        $dept = $this->departments->firstWhere('id', $departmentId);
        if (! $dept || ! filled($dept->pivot->role_id ?? null)) {
            return false;
        }

        $cachedRole = AccessCache::roleCatalog()[$dept->pivot->role_id] ?? null;

        return $cachedRole !== null && in_array($cachedRole['slug'], $rolesList, true);
    }

    /**
     * Determine if the user has the given permission, optionally scoped to a department.
     */
    public function hasPermissionTo(string $permissionSlug, Department|string|null $department = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($department !== null) {
            return $this->hasPermissionInDepartment($permissionSlug, $department);
        }

        return in_array($permissionSlug, AccessCache::permissionSlugsFor($this), true);
    }

    /**
     * Determine if the user has a given permission specifically inside a department.
     */
    public function hasPermissionInDepartment(string $permissionSlug, Department|string $department): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($permissionSlug, AccessCache::permissionSlugsFor($this, $department), true);
    }

    /**
     * Calculate the highest role level of the user (globally or within a department).
     * Memoized per instance to eliminate repeated queries in loops and policies.
     */
    public function highestRoleLevel(Department|string|null $department = null): int
    {
        if ($this->isSuperAdmin()) {
            return Role::LEVEL_SUPER_ADMIN;
        }

        if ($department === null && $this->cachedHighestRoleLevel !== null) {
            return $this->cachedHighestRoleLevel;
        }

        if (! $this->relationLoaded('roles')) {
            $this->loadMissing('roles');
        }

        $levels = $this->roles->pluck('level');

        if ($department === null) {
            if (! $this->relationLoaded('departments')) {
                $this->loadMissing('departments');
            }

            $catalog = AccessCache::roleCatalog();

            foreach ($this->departments as $dept) {
                $roleId = $dept->pivot->role_id ?? null;
                if (filled($roleId) && isset($catalog[$roleId])) {
                    $levels->push($catalog[$roleId]['level']);
                }
            }

            $highest = (int) $levels->max();
            $this->cachedHighestRoleLevel = $highest;

            return $highest;
        }

        $departmentId = $department instanceof Department ? $department->id : $department;

        if (! $this->relationLoaded('departments')) {
            $this->loadMissing('departments');
        }

        $dept = $this->departments->firstWhere('id', $departmentId);
        if ($dept && filled($dept->pivot->role_id ?? null)) {
            $cachedRole = AccessCache::roleCatalog()[$dept->pivot->role_id] ?? null;
            if ($cachedRole !== null) {
                $levels->push($cachedRole['level']);
            }
        }

        return (int) $levels->max();
    }

    /**
     * Get the primary department for the user, if assigned.
     */
    public function primaryDepartment(): ?Department
    {
        if (! $this->relationLoaded('departments')) {
            $this->loadMissing('departments');
        }

        /** @var Department|null $primary */
        $primary = $this->departments->firstWhere('pivot.is_primary', true);

        return $primary ?? $this->departments->first();
    }

    /**
     * Check if the user is a super administrator.
     */
    public function isSuperAdmin(): bool
    {
        return (bool) ($this->is_super_admin ?? false);
    }

    /**
     * Determine if the user has administrative access to manage the system or access the panel.
     */
    public function hasAdministrativeAccess(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->highestRoleLevel() >= Role::LEVEL_MANAGER
            || $this->hasRole(['admin', 'manager'])
            || $this->hasPermissionTo('users.view')
            || $this->hasPermissionTo('roles.view')
            || $this->hasPermissionTo('departments.view')
            || $this->hasPermissionTo('permissions.view')
            || $this->hasPermissionTo('audit-logs.view');
    }

    /**
     * Clear cached permission slugs and computed levels for the instance.
     */
    public function flushCachedPermissions(): void
    {
        $this->cachedHighestRoleLevel = null;

        if (isset($this->id)) {
            AccessCache::forgetUser($this->id);
        }
    }
}
