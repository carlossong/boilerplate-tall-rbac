<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Auth;

use App\Domain\Auth\Livewire\Roles\RoleIndex;
use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $perms = [
            Permission::create(['name' => 'View Roles', 'slug' => 'roles.view']),
            Permission::create(['name' => 'Create Roles', 'slug' => 'roles.create']),
            Permission::create(['name' => 'Update Roles', 'slug' => 'roles.update']),
            Permission::create(['name' => 'Delete Roles', 'slug' => 'roles.delete']),
        ];

        $this->adminRole->permissions()->sync(collect($perms)->pluck('id'));

        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($this->adminRole);
    }

    public function test_unauthorized_user_cannot_access_roles(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('admin.roles.index'))
            ->assertForbidden();
    }

    public function test_authorized_user_can_access_roles(): void
    {
        $this->actingAs($this->adminUser);

        $this->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Roles Management')
            ->assertSee('Permission Matrix')
            ->assertSee('Lvl');
    }

    public function test_can_create_role_without_assigning_permissions_in_the_form(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(RoleIndex::class)
            ->call('openCreateModal')
            ->assertSet('showingModal', true)
            ->set('form.name', 'Auditor')
            ->set('form.slug', 'auditor')
            ->set('form.description', 'Audits application data')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showingModal', false);

        $role = Role::where('slug', 'auditor')->first();
        $this->assertNotNull($role);
        $this->assertEquals('Auditor', $role->name);
        $this->assertCount(0, $role->permissions);
    }

    public function test_can_update_role(): void
    {
        $this->actingAs($this->adminUser);

        $role = Role::create(['name' => 'Old Role', 'slug' => 'old-role']);

        Livewire::test(RoleIndex::class)
            ->call('openEditModal', $role->id)
            ->assertSet('showingModal', true)
            ->set('form.name', 'New Role Title')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('New Role Title', $role->fresh()->name);
    }

    public function test_cannot_delete_admin_role(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(RoleIndex::class)
            ->call('confirmDelete', $this->adminRole->id)
            ->assertForbidden();
    }

    public function test_can_delete_and_restore_custom_role(): void
    {
        $this->actingAs($this->adminUser);

        $role = Role::create(['name' => 'Custom Role', 'slug' => 'custom-role']);

        Livewire::test(RoleIndex::class)
            ->call('confirmDelete', $role->id)
            ->assertSet('showingDeleteModal', true)
            ->call('deleteRole')
            ->assertSet('showingDeleteModal', false);

        $this->assertTrue($role->fresh()->trashed());

        Livewire::test(RoleIndex::class)
            ->set('showDeleted', true)
            ->call('restoreRole', $role->id);

        $this->assertFalse($role->fresh()->trashed());
    }
}
