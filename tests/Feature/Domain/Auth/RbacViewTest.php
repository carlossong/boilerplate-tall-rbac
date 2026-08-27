<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Auth;

use App\Domain\Auth\Livewire\Permissions\PermissionIndex;
use App\Domain\Auth\Livewire\Roles\RoleIndex;
use App\Domain\Auth\Livewire\Users\UserIndex;
use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RbacViewTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $viewerUser;

    protected User $adminUser;

    protected Role $adminRole;

    protected Role $viewerRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create([
            'name' => 'Root Super Admin',
            'email' => 'root@example.com',
        ]);

        $this->adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $perms = [
            Permission::create(['name' => 'View Users', 'slug' => 'users.view']),
            Permission::create(['name' => 'Create Users', 'slug' => 'users.create']),
            Permission::create(['name' => 'Update Users', 'slug' => 'users.update']),
            Permission::create(['name' => 'Delete Users', 'slug' => 'users.delete']),
            Permission::create(['name' => 'View Roles', 'slug' => 'roles.view']),
            Permission::create(['name' => 'Create Roles', 'slug' => 'roles.create']),
            Permission::create(['name' => 'Update Roles', 'slug' => 'roles.update']),
            Permission::create(['name' => 'Delete Roles', 'slug' => 'roles.delete']),
            Permission::create(['name' => 'View Permissions', 'slug' => 'permissions.view']),
            Permission::create(['name' => 'Create Permissions', 'slug' => 'permissions.create']),
            Permission::create(['name' => 'Update Permissions', 'slug' => 'permissions.update']),
            Permission::create(['name' => 'Delete Permissions', 'slug' => 'permissions.delete']),
        ];
        $this->adminRole->permissions()->sync(collect($perms)->pluck('id'));

        $this->adminUser = User::factory()->create([
            'name' => 'Regular Admin',
            'email' => 'admin@example.com',
            'is_super_admin' => false,
        ]);
        $this->adminUser->roles()->attach($this->adminRole);

        $this->viewerRole = Role::create(['name' => 'Viewer', 'slug' => 'viewer']);
        $viewPerms = Permission::whereIn('slug', ['users.view', 'roles.view', 'permissions.view'])->get();
        $this->viewerRole->permissions()->sync($viewPerms->pluck('id'));

        $this->viewerUser = User::factory()->create([
            'name' => 'Readonly Viewer',
            'email' => 'viewer@example.com',
            'is_super_admin' => false,
        ]);
        $this->viewerUser->roles()->attach($this->viewerRole);
    }

    public function test_sidebar_displays_administration_only_for_authorized_users(): void
    {
        $unauthorized = User::factory()->create();

        // User without permissions does not see Administration group
        $this->actingAs($unauthorized)
            ->get(route('dashboard'))
            ->assertDontSee('Administration')
            ->assertDontSee('admin.users.index');

        // Viewer user sees Administration group
        $this->actingAs($this->viewerUser)
            ->get(route('dashboard'))
            ->assertSee('Administration')
            ->assertSee(route('admin.users.index'))
            ->assertSee(route('admin.roles.index'))
            ->assertSee(route('admin.permissions.index'));
    }

    public function test_viewer_cannot_see_or_invoke_creation_and_modification_actions(): void
    {
        $this->actingAs($this->viewerUser);

        $targetUser = User::factory()->create();
        $targetRole = Role::factory()->create();
        $targetPermission = Permission::factory()->create();

        // UserIndex: viewer has no create, edit, or delete buttons
        Livewire::test(UserIndex::class)
            ->assertDontSeeHtml('wire:click="openCreateModal"')
            ->assertDontSeeHtml('wire:click="openEditModal(\''.$targetUser->id.'\')"')
            ->assertDontSeeHtml('wire:click="confirmDelete(\''.$targetUser->id.'\')"');

        // Attempting to invoke protected actions returns 403 Forbidden
        Livewire::test(UserIndex::class)
            ->call('openCreateModal')
            ->assertForbidden();

        Livewire::test(UserIndex::class)
            ->call('openEditModal', $targetUser->id)
            ->assertForbidden();

        Livewire::test(UserIndex::class)
            ->call('confirmDelete', $targetUser->id)
            ->assertForbidden();

        // RoleIndex: viewer has no create, edit, or delete buttons
        Livewire::test(RoleIndex::class)
            ->assertDontSeeHtml('wire:click="openCreateModal"')
            ->assertDontSeeHtml('wire:click="openEditModal(\''.$targetRole->id.'\')"')
            ->assertDontSeeHtml('wire:click="confirmDelete(\''.$targetRole->id.'\')"')
            ->call('openCreateModal')
            ->assertForbidden();

        // PermissionIndex: viewer has no create, edit, or delete buttons
        Livewire::test(PermissionIndex::class)
            ->assertDontSeeHtml('wire:click="openCreateModal"')
            ->assertDontSeeHtml('wire:click="openEditModal(\''.$targetPermission->id.'\')"')
            ->assertDontSeeHtml('wire:click="confirmDelete(\''.$targetPermission->id.'\')"')
            ->call('openCreateModal')
            ->assertForbidden();
    }

    public function test_super_admin_sees_all_rbac_controls_and_super_admin_toggle(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(UserIndex::class)
            ->assertSeeHtml('wire:click="openCreateModal"')
            ->call('openCreateModal')
            ->assertSee('Super Administrator Privileges');

        Livewire::test(RoleIndex::class)
            ->assertSeeHtml('wire:click="openCreateModal"');

        Livewire::test(PermissionIndex::class)
            ->assertSeeHtml('wire:click="openCreateModal"');
    }

    public function test_regular_admin_cannot_see_or_assign_super_admin_privileges(): void
    {
        $this->actingAs($this->adminUser);

        // Regular admin should not see Super Administrator Privileges checkbox in modal
        Livewire::test(UserIndex::class)
            ->call('openCreateModal')
            ->assertDontSee('Super Administrator Privileges')
            ->set('form.name', 'Hacker Attempt')
            ->set('form.email', 'hacker@example.com')
            ->set('form.password', 'password123')
            ->set('form.is_super_admin', true) // Attempts forcing via payload
            ->call('save');

        $createdUser = User::where('email', 'hacker@example.com')->first();
        $this->assertNotNull($createdUser);
        // Super admin status must have been forced to false
        $this->assertFalse($createdUser->is_super_admin);
    }

    public function test_user_cannot_delete_themselves_in_user_index_view(): void
    {
        $this->actingAs($this->adminUser);

        $otherUser = User::factory()->create(['name' => 'Target User']);

        // Can see delete option for another user
        $this->assertTrue($this->adminUser->can('delete', $otherUser));

        // Cannot see delete option for oneself
        $this->assertFalse($this->adminUser->can('delete', $this->adminUser));
    }

    public function test_admin_role_cannot_be_deleted_in_role_index_view(): void
    {
        $this->actingAs($this->adminUser);

        // The 'admin' role cannot be deleted
        $this->assertFalse($this->adminUser->can('delete', $this->adminRole));
    }
}
