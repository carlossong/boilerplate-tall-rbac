<?php

declare(strict_types=1);

namespace App\Domain\Auth\Models\Concerns;

use App\Domain\Auth\Models\Department;
use App\Domain\Auth\Models\Role;

trait HasPermissions
{
    /**
     * @var array<string>|null
     */
    protected ?array $cachedPermissionSlugs = null;

    /**
     * @var array<string, array<string>>
     */
    protected array $cachedDepartmentPermissionSlugs = [];

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

        $allCachedRoles = Role::getCachedRoles();

        foreach ($this->departments as $dept) {
            if ($dept->pivot && ! empty($dept->pivot->role_id)) {
                $roleId = $dept->pivot->role_id;
                if (isset($allCachedRoles[$roleId])) {
                    if (in_array($allCachedRoles[$roleId]->slug, $rolesList, true)) {
                        return true;
                    }
                }
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
        if (! $dept || ! $dept->pivot || empty($dept->pivot->role_id)) {
            return false;
        }

        $cachedRole = Role::getCachedRoles()->get($dept->pivot->role_id);

        return $cachedRole !== null && in_array($cachedRole->slug, $rolesList, true);
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

        if ($this->cachedPermissionSlugs === null) {
            if (! $this->relationLoaded('roles')) {
                $this->loadMissing('roles.permissions');
            } else {
                $this->roles->loadMissing('permissions');
            }

            $slugs = $this->roles
                ->flatMap(fn ($role) => $role->permissions)
                ->pluck('slug');

            // Include permissions from departmental roles in-memory
            if (! $this->relationLoaded('departments')) {
                $this->loadMissing('departments');
            }

            $allCachedRoles = Role::getCachedRoles();

            foreach ($this->departments as $dept) {
                if ($dept->pivot && ! empty($dept->pivot->role_id)) {
                    $roleId = $dept->pivot->role_id;
                    if (isset($allCachedRoles[$roleId])) {
                        $slugs = $slugs->merge($allCachedRoles[$roleId]->permissions->pluck('slug'));
                    }
                }
            }

            $this->cachedPermissionSlugs = $slugs->unique()->all();
        }

        return in_array($permissionSlug, $this->cachedPermissionSlugs, true);
    }

    /**
     * Determine if the user has a given permission specifically inside a department.
     */
    public function hasPermissionInDepartment(string $permissionSlug, Department|string $department): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $departmentId = $department instanceof Department ? $department->id : $department;

        if (isset($this->cachedDepartmentPermissionSlugs[$departmentId])) {
            return in_array($permissionSlug, $this->cachedDepartmentPermissionSlugs[$departmentId], true);
        }

        // Global permissions grant access inside all departments
        if (! $this->relationLoaded('roles')) {
            $this->loadMissing('roles.permissions');
        } else {
            $this->roles->loadMissing('permissions');
        }

        $slugs = $this->roles
            ->flatMap(fn ($role) => $role->permissions)
            ->pluck('slug');

        if (! $this->relationLoaded('departments')) {
            $this->loadMissing('departments');
        }

        $dept = $this->departments->firstWhere('id', $departmentId);
        if ($dept && $dept->pivot && ! empty($dept->pivot->role_id)) {
            $cachedRole = Role::getCachedRoles()->get($dept->pivot->role_id);
            if ($cachedRole) {
                $slugs = $slugs->merge($cachedRole->permissions->pluck('slug'));
            }
        }

        $this->cachedDepartmentPermissionSlugs[$departmentId] = $slugs->unique()->all();

        return in_array($permissionSlug, $this->cachedDepartmentPermissionSlugs[$departmentId], true);
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

            $allCachedRoles = Role::getCachedRoles();

            foreach ($this->departments as $dept) {
                if ($dept->pivot && ! empty($dept->pivot->role_id)) {
                    $roleId = $dept->pivot->role_id;
                    if (isset($allCachedRoles[$roleId])) {
                        $levels->push($allCachedRoles[$roleId]->level);
                    }
                }
            }

            $highest = (int) ($levels->max() ?? 0);
            $this->cachedHighestRoleLevel = $highest;

            return $highest;
        }

        $departmentId = $department instanceof Department ? $department->id : $department;

        if (! $this->relationLoaded('departments')) {
            $this->loadMissing('departments');
        }

        $dept = $this->departments->firstWhere('id', $departmentId);
        if ($dept && $dept->pivot && ! empty($dept->pivot->role_id)) {
            $cachedRole = Role::getCachedRoles()->get($dept->pivot->role_id);
            if ($cachedRole) {
                $levels->push($cachedRole->level);
            }
        }

        return (int) ($levels->max() ?? 0);

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
        $this->cachedPermissionSlugs = null;
        $this->cachedDepartmentPermissionSlugs = [];
        $this->cachedHighestRoleLevel = null;
    }
}
