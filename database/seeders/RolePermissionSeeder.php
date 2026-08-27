<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Auth\Models\Department;
use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Register Canonical Permissions
        $permissions = [
            // Users
            ['name' => 'View Users', 'slug' => 'users.view', 'description' => 'Allows viewing the users list and profile details.'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'description' => 'Allows registering new user accounts.'],
            ['name' => 'Update Users', 'slug' => 'users.update', 'description' => 'Allows updating user profiles and role assignments.'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'description' => 'Allows deleting and restoring user accounts.'],

            // Departments
            ['name' => 'View Departments', 'slug' => 'departments.view', 'description' => 'Allows viewing company departments and units.'],
            ['name' => 'Create Departments', 'slug' => 'departments.create', 'description' => 'Allows creating new departments.'],
            ['name' => 'Update Departments', 'slug' => 'departments.update', 'description' => 'Allows updating department details and role links.'],
            ['name' => 'Delete Departments', 'slug' => 'departments.delete', 'description' => 'Allows deleting and restoring departments.'],

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

            // Audit
            ['name' => 'View Audit Logs', 'slug' => 'audit-logs.view', 'description' => 'Allows viewing who assigned or revoked roles and permissions.'],
        ];

        $createdPermissions = [];
        foreach ($permissions as $permData) {
            $createdPermissions[$permData['slug']] = Permission::updateOrCreate(
                ['slug' => $permData['slug']],
                [
                    'name' => $permData['name'],
                    'description' => $permData['description'],
                    'group' => Permission::groupFromSlug($permData['slug']),
                ],
            );
        }

        // 2. Register Departments
        $executive = Department::updateOrCreate(
            ['slug' => 'executive-board'],
            [
                'name' => 'Executive Board',
                'description' => 'Executive leadership, corporate governance, and global strategy.',
                'is_active' => true,
            ],
        );

        $finance = Department::updateOrCreate(
            ['slug' => 'finance'],
            [
                'name' => 'Finance Department',
                'description' => 'Accounts payable, accounts receivable, budget approvals, and treasury.',
                'is_active' => true,
            ],
        );

        $operations = Department::updateOrCreate(
            ['slug' => 'operations'],
            [
                'name' => 'Operations & Logistics',
                'description' => 'Fleet management, routing, shifts, and operational logistics.',
                'is_active' => true,
            ],
        );

        $hr = Department::updateOrCreate(
            ['slug' => 'human-resources'],
            [
                'name' => 'Human Resources',
                'description' => 'Talent management, department organization, and employee onboarding.',
                'is_active' => true,
            ],
        );

        // 3. Register Roles with Hierarchical Levels
        $adminRole = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrator',
                'level' => 80,
                'is_system' => true,
                'description' => 'Full administrative access to all company resources.',
            ],
        );
        $adminRole->permissions()->sync(collect($createdPermissions)->pluck('id'));

        $managerRole = Role::updateOrCreate(
            ['slug' => 'manager'],
            [
                'name' => 'Department Manager',
                'level' => 50,
                'description' => 'Team management and operational authorization within the department.',
            ],
        );
        $managerPermissionIds = collect($createdPermissions)
            ->filter(fn ($p, $slug) => str_starts_with($slug, 'users.') || str_starts_with($slug, 'departments.view'))
            ->pluck('id');
        $managerRole->permissions()->sync($managerPermissionIds);

        $operatorRole = Role::updateOrCreate(
            ['slug' => 'operator'],
            [
                'name' => 'Operator',
                'level' => 20,
                'description' => 'Basic operational access to execute sector routines and tasks.',
            ],
        );

        $viewerRole = Role::updateOrCreate(
            ['slug' => 'viewer'],
            [
                'name' => 'Viewer',
                'level' => 10,
                'description' => 'Read-only access to view system users, roles, and permissions.',
            ],
        );
        $viewPermissionIds = collect($createdPermissions)
            ->filter(fn ($p, $slug) => str_ends_with($slug, '.view'))
            ->pluck('id');
        $viewerRole->permissions()->sync($viewPermissionIds);

        // Link roles to model departments
        $finance->roles()->syncWithoutDetaching([$adminRole->id, $managerRole->id, $viewerRole->id]);
        $operations->roles()->syncWithoutDetaching([$managerRole->id, $operatorRole->id, $viewerRole->id]);
        $executive->roles()->syncWithoutDetaching([$adminRole->id, $managerRole->id]);

        // 4. Register Demo Users
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
        $superAdmin->departments()->sync([
            $executive->id => ['id' => (string) Str::uuid(), 'role_id' => $adminRole->id, 'is_primary' => true],
            $finance->id => ['id' => (string) Str::uuid(), 'role_id' => $adminRole->id, 'is_primary' => false],
        ]);

        $managerUser = User::updateOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Operations Manager',
                'password' => Hash::make('password'),
                'is_super_admin' => false,
                'email_verified_at' => now(),
            ],
        );
        $managerUser->roles()->sync([$managerRole->id]);
        $managerUser->departments()->sync([
            $operations->id => ['id' => (string) Str::uuid(), 'role_id' => $managerRole->id, 'is_primary' => true],
        ]);

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
        $viewerUser->departments()->sync([
            $finance->id => ['id' => (string) Str::uuid(), 'role_id' => $viewerRole->id, 'is_primary' => true],
        ]);
    }
}
