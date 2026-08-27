<?php

declare(strict_types=1);

namespace App\Domain\Auth\Support;

use App\Domain\Auth\Models\Department;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class AccessCache
{
    public const string KEY_ROLES = 'auth.roles.catalog';

    public const string TAG_ROLES = 'auth.roles';

    public const string TAG_PERMISSIONS = 'auth.permissions';

    /**
     * @var array<string, array{slug: string, level: int, permissions: list<string>}>|null
     */
    private static ?array $roleCatalog = null;

    /**
     * @var array<string, list<string>>
     */
    private static array $userPermissionSlugs = [];

    public static function roleTag(string $roleId): string
    {
        return 'role:'.$roleId;
    }

    public static function userPermissionsTag(string $userId): string
    {
        return 'user:'.$userId.':permissions';
    }

    public static function userPermissionsKey(string $userId): string
    {
        return 'user:'.$userId.':permissions';
    }

    public static function supportsTags(): bool
    {
        return Cache::supportsTags();
    }

    /**
     * @return array<string, array{slug: string, level: int, permissions: list<string>}>
     */
    public static function roleCatalog(): array
    {
        if (self::$roleCatalog !== null) {
            return self::$roleCatalog;
        }

        /** @var array<string, array{slug: string, level: int, permissions: list<string>}> $catalog */
        $catalog = self::remember(
            [self::TAG_ROLES, self::TAG_PERMISSIONS],
            self::KEY_ROLES,
            function (): array {
                return Role::with('permissions')
                    ->get()
                    ->mapWithKeys(fn (Role $role): array => [
                        $role->id => [
                            'slug' => $role->slug,
                            'level' => (int) $role->level,
                            'permissions' => $role->permissions->pluck('slug')->values()->all(),
                        ],
                    ])
                    ->all();
            },
        );

        return self::$roleCatalog = $catalog;
    }

    /**
     * @return list<string>
     */
    public static function permissionSlugsFor(User $user, Department|string|null $department = null): array
    {
        if ($department !== null) {
            return self::resolvePermissionSlugs($user, $department);
        }

        if (isset(self::$userPermissionSlugs[$user->id])) {
            return self::$userPermissionSlugs[$user->id];
        }

        $user->unsetRelation('roles');
        $user->unsetRelation('departments');
        $user->unsetRelation('permissions');

        $roleIds = self::roleIdsFor($user);

        /** @var list<string> $slugs */
        $slugs = self::remember(
            self::userCacheTags($user->id, $roleIds),
            self::userPermissionsKey($user->id),
            fn (): array => self::resolvePermissionSlugs($user),
        );

        return self::$userPermissionSlugs[$user->id] = $slugs;
    }

    public static function forgetRole(string $roleId): void
    {
        self::$roleCatalog = null;
        self::$userPermissionSlugs = [];

        if (self::supportsTags()) {
            Cache::tags([self::roleTag($roleId), self::TAG_ROLES])->flush();

            return;
        }

        Cache::forget(self::KEY_ROLES);

        foreach (self::userIdsForRole($roleId) as $userId) {
            Cache::forget(self::userPermissionsKey($userId));
        }
    }

    public static function forgetUser(string $userId): void
    {
        unset(self::$userPermissionSlugs[$userId]);

        if (self::supportsTags()) {
            Cache::tags([self::userPermissionsTag($userId)])->flush();

            return;
        }

        Cache::forget(self::userPermissionsKey($userId));
    }

    public static function forgetPermissions(): void
    {
        self::$roleCatalog = null;
        self::$userPermissionSlugs = [];

        if (self::supportsTags()) {
            Cache::tags([self::TAG_PERMISSIONS, self::TAG_ROLES])->flush();

            return;
        }

        Cache::forget(self::KEY_ROLES);

        foreach (self::allCachedUserIds() as $userId) {
            Cache::forget(self::userPermissionsKey($userId));
        }
    }

    /**
     * Drop request-level memoization and shared catalog keys. Used by tests.
     */
    public static function reset(): void
    {
        self::$roleCatalog = null;
        self::$userPermissionSlugs = [];
        Cache::forget(self::KEY_ROLES);

        if (self::supportsTags()) {
            Cache::tags([self::TAG_ROLES, self::TAG_PERMISSIONS])->flush();
        }
    }

    /**
     * @template T
     *
     * @param  list<string>  $tags
     * @param  \Closure(): T  $callback
     * @return T
     */
    private static function remember(array $tags, string $key, \Closure $callback): mixed
    {
        if (self::supportsTags()) {
            return Cache::tags($tags)->rememberForever($key, $callback);
        }

        return Cache::rememberForever($key, $callback);
    }

    /**
     * @param  list<string>  $roleIds
     * @return list<string>
     */
    private static function userCacheTags(string $userId, array $roleIds): array
    {
        $tags = [self::TAG_PERMISSIONS, self::userPermissionsTag($userId)];

        foreach ($roleIds as $roleId) {
            $tags[] = self::roleTag($roleId);
        }

        return $tags;
    }

    /**
     * @return list<string>
     */
    private static function resolvePermissionSlugs(User $user, Department|string|null $department = null): array
    {
        $catalog = self::roleCatalog();
        $user->loadMissing(['roles', 'departments', 'permissions']);

        $roleIds = $user->roles->pluck('id');

        if ($department === null) {
            foreach ($user->departments as $dept) {
                $roleId = self::departmentRoleId($dept);
                if ($roleId !== null) {
                    $roleIds->push($roleId);
                }
            }
        } else {
            $departmentId = $department instanceof Department ? $department->id : $department;
            $dept = $user->departments->firstWhere('id', $departmentId);
            $roleId = $dept !== null ? self::departmentRoleId($dept) : null;
            if ($roleId !== null) {
                $roleIds->push($roleId);
            }
        }

        $slugs = [];
        foreach ($roleIds->unique()->all() as $roleId) {
            if (! isset($catalog[$roleId])) {
                continue;
            }

            foreach ($catalog[$roleId]['permissions'] as $slug) {
                $slugs[$slug] = true;
            }
        }

        foreach ($user->permissions as $permission) {
            $slugs[$permission->slug] = true;
        }

        return array_keys($slugs);
    }

    /**
     * @return list<string>
     */
    private static function roleIdsFor(User $user): array
    {
        $user->loadMissing(['roles', 'departments']);

        $ids = $user->roles->pluck('id')->map(fn (mixed $id): string => (string) $id)->all();

        foreach ($user->departments as $dept) {
            $roleId = self::departmentRoleId($dept);
            if ($roleId !== null) {
                $ids[] = $roleId;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return list<string>
     */
    private static function userIdsForRole(string $roleId): array
    {
        return self::uniqueStringIds(array_merge(
            DB::table('role_user')->where('role_id', $roleId)->pluck('user_id')->all(),
            DB::table('department_user')->where('role_id', $roleId)->pluck('user_id')->all(),
        ));
    }

    /**
     * @return list<string>
     */
    private static function allCachedUserIds(): array
    {
        return self::uniqueStringIds(array_merge(
            DB::table('role_user')->pluck('user_id')->all(),
            DB::table('department_user')->whereNotNull('role_id')->pluck('user_id')->all(),
            DB::table('permission_user')->pluck('user_id')->all(),
        ));
    }

    private static function departmentRoleId(Department $department): ?string
    {
        $roleId = data_get($department->pivot, 'role_id');

        return filled($roleId) ? (string) $roleId : null;
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return list<string>
     */
    private static function uniqueStringIds(array $ids): array
    {
        $unique = [];

        foreach ($ids as $id) {
            if ($id === null || $id === '') {
                continue;
            }

            $unique[(string) $id] = true;
        }

        return array_keys($unique);
    }
}
