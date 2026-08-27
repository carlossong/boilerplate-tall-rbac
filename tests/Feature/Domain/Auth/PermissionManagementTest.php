<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Auth;

use App\Domain\Auth\Livewire\Permissions\PermissionIndex;
use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $perms = [
            Permission::create(['name' => 'View Permissions', 'slug' => 'permissions.view']),
            Permission::create(['name' => 'Create Permissions', 'slug' => 'permissions.create']),
            Permission::create(['name' => 'Update Permissions', 'slug' => 'permissions.update']),
            Permission::create(['name' => 'Delete Permissions', 'slug' => 'permissions.delete']),
        ];

        $this->adminRole->permissions()->sync(collect($perms)->pluck('id'));

        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($this->adminRole);
    }

    public function test_unauthorized_user_cannot_access_permissions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('admin.permissions.index'))
            ->assertForbidden();
    }

    public function test_authorized_user_can_access_permissions(): void
    {
        $this->actingAs($this->adminUser);

        $this->get(route('admin.permissions.index'))
            ->assertOk()
            ->assertSee('Permissions Management');
    }

    public function test_permission_slug_regex_validation(): void
    {
        $this->actingAs($this->adminUser);

        // Slug inválido sem ponto
        Livewire::test(PermissionIndex::class)
            ->call('openCreateModal')
            ->set('form.name', 'Invalid Permission')
            ->set('form.slug', 'invalidslug')
            ->call('save')
            ->assertHasErrors(['form.slug']);

        // Slug válido no padrão resource.action
        Livewire::test(PermissionIndex::class)
            ->call('openCreateModal')
            ->set('form.name', 'Valid Permission')
            ->set('form.slug', 'invoices.download')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('permissions', ['slug' => 'invoices.download']);
        $this->assertSame('invoices', Permission::where('slug', 'invoices.download')->value('group'));
    }

    public function test_permissions_can_be_filtered_by_group_column(): void
    {
        $this->actingAs($this->adminUser);

        Permission::create(['name' => 'Export Reports', 'slug' => 'reports.export', 'group' => 'billing']);
        Permission::create(['name' => 'View Users Extra', 'slug' => 'users.export']);

        Livewire::test(PermissionIndex::class)
            ->set('groupFilter', 'billing')
            ->assertSee('Export Reports')
            ->assertDontSee('View Users Extra');
    }

    public function test_explicit_group_can_differ_from_slug_prefix(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(PermissionIndex::class)
            ->call('openCreateModal')
            ->set('form.name', 'Export Reports')
            ->set('form.slug', 'reports.export')
            ->set('form.group', 'billing')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('permissions', [
            'slug' => 'reports.export',
            'group' => 'billing',
        ]);
    }

    public function test_can_update_permission(): void
    {
        $this->actingAs($this->adminUser);

        $perm = Permission::create(['name' => 'Old Perm', 'slug' => 'old.perm']);

        Livewire::test(PermissionIndex::class)
            ->call('openEditModal', $perm->id)
            ->assertSet('showingModal', true)
            ->set('form.name', 'Updated Perm')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('Updated Perm', $perm->fresh()->name);
    }

    public function test_can_delete_and_restore_permission(): void
    {
        $this->actingAs($this->adminUser);

        $perm = Permission::create(['name' => 'To Delete', 'slug' => 'to.delete']);

        Livewire::test(PermissionIndex::class)
            ->call('confirmDelete', $perm->id)
            ->assertSet('showingDeleteModal', true)
            ->call('deletePermission')
            ->assertSet('showingDeleteModal', false);

        $this->assertTrue($perm->fresh()->trashed());

        Livewire::test(PermissionIndex::class)
            ->set('showDeleted', true)
            ->call('restorePermission', $perm->id);

        $this->assertFalse($perm->fresh()->trashed());
    }
}
