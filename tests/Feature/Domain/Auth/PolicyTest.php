<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Auth;

use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_bypasses_all_policies_and_gates(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->assertTrue($superAdmin->can('users.view'));
        $this->assertTrue($superAdmin->can('users.delete'));
        $this->assertTrue($superAdmin->can('roles.delete'));
        $this->assertTrue($superAdmin->can('nonexistent.ability'));
    }

    public function test_user_without_permissions_is_denied(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('users.view'));
        $this->assertFalse($user->can('roles.view'));
        $this->assertFalse($user->can('permissions.view'));
    }

    public function test_user_with_role_and_permission_is_allowed(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['slug' => 'manager']);
        $permission = Permission::factory()->create(['slug' => 'users.view']);

        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        // Update gates with the new permission
        Gate::define('users.view', fn ($u) => $u->hasPermissionTo('users.view'));

        $this->assertTrue($user->can('users.view'));
        $this->assertFalse($user->can('users.delete'));
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $permission = Permission::factory()->create(['slug' => 'users.delete']);

        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        Gate::define('users.delete', fn ($u) => $u->hasPermissionTo('users.delete'));

        $otherUser = User::factory()->create();

        // Can delete another user
        $this->assertTrue(Gate::forUser($user)->allows('delete', $otherUser));

        // Cannot delete oneself
        $this->assertFalse(Gate::forUser($user)->allows('delete', $user));
    }

    public function test_admin_role_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['level' => 50]);
        $permission = Permission::factory()->create(['slug' => 'roles.delete']);

        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        Gate::define('roles.delete', fn ($u) => $u->hasPermissionTo('roles.delete'));

        $adminRole = Role::factory()->create(['slug' => 'admin', 'level' => 80]);
        $customRole = Role::factory()->create(['slug' => 'custom', 'level' => 10]);

        $this->assertTrue(Gate::forUser($user)->allows('delete', $customRole));
        $this->assertFalse(Gate::forUser($user)->allows('delete', $adminRole));
    }
}
