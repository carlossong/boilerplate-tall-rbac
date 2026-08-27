<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Auth;

use App\Domain\Auth\Models\Department;
use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GateDynamicTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_checks_resolve_dynamically_without_per_slug_gate_definitions(): void
    {
        $permission = Permission::create([
            'name' => 'Reports Export',
            'slug' => 'reports.export',
        ]);

        $role = Role::create([
            'name' => 'Analyst',
            'slug' => 'analyst',
        ]);
        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->assertFalse($this->gateIsDefined($permission->slug));
        $this->assertTrue($user->can('reports.export'));

        $unauthorizedUser = User::factory()->create();
        $this->assertFalse($unauthorizedUser->can('reports.export'));
    }

    public function test_newly_created_permission_slugs_are_authorizable_without_rebooting(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $permission = Permission::create([
            'name' => 'Invoices Void',
            'slug' => 'invoices.void',
        ]);

        $this->assertTrue($admin->can('invoices.void'));
        $this->assertFalse(User::factory()->create()->can($permission->slug));
    }

    public function test_attaching_a_permission_to_a_role_invalidates_cached_user_permissions(): void
    {
        $export = Permission::factory()->create(['slug' => 'reports.export']);
        $view = Permission::factory()->create(['slug' => 'reports.view']);
        $role = Role::factory()->create();
        $role->permissions()->attach($export);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->assertTrue($user->hasPermissionTo('reports.export'));
        $this->assertFalse($user->hasPermissionTo('reports.view'));

        $role->permissions()->attach($view);

        $this->assertTrue($user->hasPermissionTo('reports.view'));
        $this->assertTrue($user->fresh()->hasPermissionTo('reports.view'));
    }

    public function test_detaching_a_role_from_a_user_invalidates_that_users_permission_cache(): void
    {
        $permission = Permission::factory()->create(['slug' => 'reports.export']);
        $role = Role::factory()->create();
        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->assertTrue($user->hasPermissionTo('reports.export'));

        $user->roles()->detach($role);

        $this->assertFalse($user->hasPermissionTo('reports.export'));
        $this->assertFalse($user->fresh()->hasPermissionTo('reports.export'));
    }

    public function test_updating_a_role_invalidates_the_role_catalog(): void
    {
        $role = Role::factory()->create(['slug' => 'analyst', 'level' => 20]);
        $user = User::factory()->create();
        $user->departments()->attach(
            Department::factory()->create(),
            [
                'id' => (string) Str::uuid(),
                'role_id' => $role->id,
                'is_primary' => true,
            ],
        );

        $this->assertTrue($user->hasRole('analyst'));

        $role->update(['slug' => 'senior-analyst']);

        $this->assertFalse($user->fresh()->hasRole('analyst'));
        $this->assertTrue($user->fresh()->hasRole('senior-analyst'));
    }

    private function gateIsDefined(string $ability): bool
    {
        return app(Gate::class)->has($ability);
    }
}
