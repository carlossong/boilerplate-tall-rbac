<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Auth;

use App\Domain\Auth\Actions\DeleteRoleAction;
use App\Domain\Auth\Actions\UpdateRoleAction;
use App\Domain\Auth\DTOs\RoleData;
use App\Domain\Auth\Livewire\Roles\RoleIndex;
use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SystemRoleProtectionTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $adminUser;

    private Role $adminRole;

    private Role $customSystemRole;

    private Role $regularRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();

        $this->adminRole = Role::factory()->admin()->create();

        $perms = [
            Permission::create(['name' => 'View Roles', 'slug' => 'roles.view']),
            Permission::create(['name' => 'Create Roles', 'slug' => 'roles.create']),
            Permission::create(['name' => 'Update Roles', 'slug' => 'roles.update']),
            Permission::create(['name' => 'Delete Roles', 'slug' => 'roles.delete']),
        ];
        $this->adminRole->permissions()->sync(collect($perms)->pluck('id'));

        $this->customSystemRole = Role::factory()->system()->create([
            'name' => 'Custom System Role',
            'slug' => 'custom-system',
            'level' => 70,
        ]);
        $this->regularRole = Role::factory()->create([
            'name' => 'Regular Role',
            'slug' => 'regular-role',
            'level' => 20,
            'is_system' => false,
        ]);

        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($this->adminRole);
    }

    public function test_system_role_cannot_be_deleted_via_action(): void
    {
        $action = app(DeleteRoleAction::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('System roles cannot be deleted.');

        $action($this->adminRole);
    }

    public function test_custom_system_role_cannot_be_deleted_via_action(): void
    {
        $action = app(DeleteRoleAction::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('System roles cannot be deleted.');

        $action($this->customSystemRole);
    }

    public function test_system_role_cannot_be_updated_via_action(): void
    {
        $action = app(UpdateRoleAction::class);

        $data = new RoleData(
            name: 'Hacked Name',
            slug: 'admin',
            level: 10,
            description: 'Downgraded',
            permissionIds: [],
            departmentIds: [],
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('System roles cannot be edited.');

        $action($this->adminRole, $data);
    }

    public function test_policy_denies_update_and_delete_on_system_roles_for_regular_admins(): void
    {
        $this->assertFalse($this->adminUser->can('update', $this->adminRole));
        $this->assertFalse($this->adminUser->can('delete', $this->adminRole));

        $this->assertFalse($this->adminUser->can('update', $this->customSystemRole));
        $this->assertFalse($this->adminUser->can('delete', $this->customSystemRole));

        // Regular roles can be updated/deleted according to hierarchy
        $this->assertTrue($this->adminUser->can('update', $this->regularRole));
        $this->assertTrue($this->adminUser->can('delete', $this->regularRole));
    }

    public function test_role_index_prevents_opening_edit_and_delete_modals_for_system_roles(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(RoleIndex::class)
            ->call('openEditModal', $this->adminRole->id)
            ->assertForbidden();

        Livewire::test(RoleIndex::class)
            ->call('confirmDelete', $this->adminRole->id)
            ->assertForbidden();
    }

    public function test_regular_role_can_be_edited_and_deleted_normally(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(RoleIndex::class)
            ->call('openEditModal', $this->regularRole->id)
            ->assertSet('showingModal', true)
            ->set('form.name', 'Updated Regular Role')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'id' => $this->regularRole->id,
            'name' => 'Updated Regular Role',
        ]);

        Livewire::test(RoleIndex::class)
            ->call('confirmDelete', $this->regularRole->id)
            ->assertSet('showingDeleteModal', true)
            ->call('deleteRole')
            ->assertSet('showingDeleteModal', false);

        $this->assertSoftDeleted('roles', [
            'id' => $this->regularRole->id,
        ]);
    }
}
