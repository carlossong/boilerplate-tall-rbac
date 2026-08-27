<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Cadastrar Permissões Canônicas
        $permissions = [
            // Users
            ['name' => 'View Users', 'slug' => 'users.view', 'description' => 'Allows viewing the users list and profile details.'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'description' => 'Allows registering new user accounts.'],
            ['name' => 'Update Users', 'slug' => 'users.update', 'description' => 'Allows updating user profiles and role assignments.'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'description' => 'Allows deleting and restoring user accounts.'],

            // Roles
            ['name' => 'View Roles', 'slug' => 'roles.view', 'description' => 'Allows viewing system roles and assigned permissions.'],
            ['name' => 'Create Roles', 'slug' => 'roles.create', 'description' => 'Allows defining new system roles.'],
            ['name' => 'Update Roles', 'slug' => 'roles.update', 'description' => 'Allows updating roles and permission assignments.'],
            ['name' => 'Delete Roles', 'slug' => 'roles.delete', 'description' => 'Allows soft-deleting and restoring roles.'],

            // Permissions
            ['name' => 'View Permissions', 'slug' => 'permissions.view', 'description' => 'Allows viewing permission abilities and system slugs.'],
            ['name' => 'Create Permissions', 'slug' => 'permissions.create', 'description' => 'Allows registering new custom permission slugs.'],
            ['name' => 'Update Permissions', 'slug' => 'permissions.update', 'description' => 'Allows updating permission names and descriptions.'],
            ['name' => 'Delete Permissions', 'slug' => 'permissions.delete', 'description' => 'Allows deleting permission abilities.'],
        ];

        $createdPermissions = [];
        foreach ($permissions as $permData) {
            $createdPermissions[$permData['slug']] = Permission::updateOrCreate(
                ['slug' => $permData['slug']],
                ['name' => $permData['name'], 'description' => $permData['description']],
            );
        }

        Permission::flushCache();

        // 2. Cadastrar Roles
        $adminRole = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrator',
                'description' => 'Full administrative access to all application resources.',
            ],
        );
        $adminRole->permissions()->sync(collect($createdPermissions)->pluck('id'));

        $viewerRole = Role::updateOrCreate(
            ['slug' => 'viewer'],
            [
                'name' => 'Viewer',
                'description' => 'Read-only access to view system users, roles, and permissions.',
            ],
        );
        $viewPermissionIds = collect($createdPermissions)
            ->filter(fn ($p, $slug) => str_ends_with($slug, '.view'))
            ->pluck('id');
        $viewerRole->permissions()->sync($viewPermissionIds);

        // 3. Cadastrar Usuários de Bootstrap
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password'),
                'is_super_admin' => true,
                'email_verified_at' => now(),
            ],
        );
        $superAdmin->roles()->sync([$adminRole->id]);

        $viewerUser = User::updateOrCreate(
            ['email' => 'viewer@example.com'],
            [
                'name' => 'Viewer User',
                'password' => Hash::make('password'),
                'is_super_admin' => false,
                'email_verified_at' => now(),
            ],
        );
        $viewerUser->roles()->sync([$viewerRole->id]);
    }
}
