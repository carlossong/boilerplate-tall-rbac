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

        if (is_string($roles)) {
            $roles = [$roles];
        }

        if (! $this->relationLoaded('roles')) {
            $this->loadMissing('roles');
        }

        if ($this->roles->pluck('slug')->intersect($roles)->isNotEmpty()) {
            return true;
        }

        // Also check if any departmental role matches
        if (! $this->relationLoaded('departments')) {
            $this->loadMissing('departments');
        }

        $departmentRoleIds = $this->departments
            ->pluck('pivot.role_id')
            ->filter()
            ->unique()
            ->all();

        if (! empty($departmentRoleIds)) {
            $matchingRoles = Role::query()
                ->whereIn('id', $departmentRoleIds)
                ->whereIn('slug', $roles)
                ->exists();

            if ($matchingRoles) {
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

        if (is_string($roles)) {
            $roles = [$roles];
        }

        // Global roles (like admin) apply across departments
        if (! $this->relationLoaded('roles')) {
            $this->loadMissing('roles');
        }

        if ($this->roles->pluck('slug')->intersect($roles)->isNotEmpty()) {
            return true;
        }

        $departmentId = $department instanceof Department ? $department->id : $department;

        if (! $this->relationLoaded('departments')) {
            $this->loadMissing('departments');
        }

        $dept = $this->departments->firstWhere('id', $departmentId);
        $pivot = $dept?->pivot;
        if (! $dept || ! $pivot || empty($pivot->role_id)) {
            return false;
        }

        /** @var Role|null $role */
        $role = Role::query()->where('id', $pivot->role_id)->first();

        return $role !== null && in_array($role->slug, $roles, true);
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

            // Include permissions from departmental roles
            if (! $this->relationLoaded('departments')) {
                $this->loadMissing('departments');
            }

            $departmentRoleIds = $this->departments
                ->pluck('pivot.role_id')
                ->filter()
                ->unique()
                ->all();

            if (! empty($departmentRoleIds)) {
                $deptPermissions = Role::with('permissions')
                    ->whereIn('id', $departmentRoleIds)
                    ->get()
                    ->flatMap(fn ($role) => $role->permissions)
                    ->pluck('slug');

                $slugs = $slugs->merge($deptPermissions);
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

        // Global permissions also grant access inside any department
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
        $pivot = $dept?->pivot;
        if ($dept && $pivot && ! empty($pivot->role_id)) {
            /** @var Role|null $role */
            $role = Role::with('permissions')->where('id', $pivot->role_id)->first();
            if ($role) {
                $slugs = $slugs->merge($role->permissions->pluck('slug'));
            }
        }

        $this->cachedDepartmentPermissionSlugs[$departmentId] = $slugs->unique()->all();

        return in_array($permissionSlug, $this->cachedDepartmentPermissionSlugs[$departmentId], true);
    }

    /**
     * Calculate the highest role level of the user (globally or within a department).
     */
    public function highestRoleLevel(Department|string|null $department = null): int
    {
        if ($this->isSuperAdmin()) {
            return 100;
        }

        if (! $this->relationLoaded('roles')) {
            $this->loadMissing('roles');
        }

        $levels = $this->roles->pluck('level');

        if ($department === null) {
            // Consider all assigned roles, including department-specific roles
            if (! $this->relationLoaded('departments')) {
                $this->loadMissing('departments');
            }

            $deptRoleIds = $this->departments
                ->pluck('pivot.role_id')
                ->filter()
                ->unique()
                ->all();

            if (! empty($deptRoleIds)) {
                $deptLevels = Role::query()->whereIn('id', $deptRoleIds)->pluck('level');
                $levels = $levels->merge($deptLevels);
            }
        } else {
            $departmentId = $department instanceof Department ? $department->id : $department;

            if (! $this->relationLoaded('departments')) {
                $this->loadMissing('departments');
            }

            $dept = $this->departments->firstWhere('id', $departmentId);
            $pivot = $dept?->pivot;
            if ($dept && $pivot && ! empty($pivot->role_id)) {
                /** @var Role|null $role */
                $role = Role::query()->where('id', $pivot->role_id)->first();
                if ($role) {
                    $levels->push($role->level);
                }
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
     * Clear cached permission slugs for the instance.
     */
    public function flushCachedPermissions(): void
    {
        $this->cachedPermissionSlugs = null;
        $this->cachedDepartmentPermissionSlugs = [];
    }
}
