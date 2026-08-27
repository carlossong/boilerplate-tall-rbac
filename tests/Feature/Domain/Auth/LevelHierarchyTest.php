<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Auth;

use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class LevelHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_lower_level_cannot_update_user_with_higher_or_equal_level(): void
    {
        // Setup permissions
        $updatePermission = Permission::factory()->create(['slug' => 'users.update']);
        $deletePermission = Permission::factory()->create(['slug' => 'users.delete']);

        Gate::define('users.update', fn ($u) => $u->hasPermissionTo('users.update'));
        Gate::define('users.delete', fn ($u) => $u->hasPermissionTo('users.delete'));

        // Manager (Level 50)
        $managerRole = Role::factory()->create(['name' => 'Manager', 'slug' => 'manager', 'level' => 50]);
        $managerRole->permissions()->attach([$updatePermission->id, $deletePermission->id]);

        $manager = User::factory()->create();
        $manager->roles()->attach($managerRole);

        // Director (Level 80)
        $directorRole = Role::factory()->create(['name' => 'Director', 'slug' => 'director', 'level' => 80]);
        $director = User::factory()->create();
        $director->roles()->attach($directorRole);

        // Peer Manager (Level 50)
        $peerManager = User::factory()->create();
        $peerManager->roles()->attach($managerRole);

        // Operator (Level 20)
        $operatorRole = Role::factory()->create(['name' => 'Operator', 'slug' => 'operator', 'level' => 20]);
        $operator = User::factory()->create();
        $operator->roles()->attach($operatorRole);

        // Assertions: Manager cannot update Director (50 < 80)
        $this->assertFalse($manager->can('update', $director));
        $this->assertFalse($manager->can('delete', $director));

        // Assertions: Manager cannot update or delete peer Manager (50 <= 50)
        $this->assertFalse($manager->can('update', $peerManager));
        $this->assertFalse($manager->can('delete', $peerManager));

        // Assertions: Manager CAN update and delete Operator (50 > 20)
        $this->assertTrue($manager->can('update', $operator));
        $this->assertTrue($manager->can('delete', $operator));
    }

    public function test_user_cannot_delete_themselves_regardless_of_level(): void
    {
        $adminRole = Role::factory()->create(['level' => 80]);
        $deletePermission = Permission::factory()->create(['slug' => 'users.delete']);
        $adminRole->permissions()->attach($deletePermission);
        Gate::define('users.delete', fn ($u) => $u->hasPermissionTo('users.delete'));

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $this->assertFalse($admin->can('delete', $admin));
    }

    public function test_super_admin_bypasses_all_level_restrictions(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $director = User::factory()->create();
        $directorRole = Role::factory()->create(['level' => 90]);
        $director->roles()->attach($directorRole);

        $this->assertTrue($superAdmin->can('update', $director));
        $this->assertTrue($superAdmin->can('delete', $director));
    }

    public function test_role_policy_restricts_actions_on_higher_level_roles(): void
    {
        $rolePermission = Permission::factory()->create(['slug' => 'roles.update']);
        $deletePermission = Permission::factory()->create(['slug' => 'roles.delete']);
        Gate::define('roles.update', fn ($u) => $u->hasPermissionTo('roles.update'));
        Gate::define('roles.delete', fn ($u) => $u->hasPermissionTo('roles.delete'));

        $managerRole = Role::factory()->create(['name' => 'Manager', 'slug' => 'manager', 'level' => 50]);
        $managerRole->permissions()->attach([$rolePermission->id, $deletePermission->id]);

        $manager = User::factory()->create();
        $manager->roles()->attach($managerRole);

        $highLevelRole = Role::factory()->create(['name' => 'Executive', 'slug' => 'executive', 'level' => 90]);
        $lowLevelRole = Role::factory()->create(['name' => 'Intern', 'slug' => 'intern', 'level' => 10]);

        // Manager cannot edit or delete higher level role
        $this->assertFalse($manager->can('update', $highLevelRole));
        $this->assertFalse($manager->can('delete', $highLevelRole));

        // Manager CAN edit and delete lower level role
        $this->assertTrue($manager->can('update', $lowLevelRole));
        $this->assertTrue($manager->can('delete', $lowLevelRole));
    }
}
