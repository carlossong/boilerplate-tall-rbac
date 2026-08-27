<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Auth;

use App\Domain\Auth\Actions\DeleteUserAction;
use App\Domain\Auth\Actions\UpdateUserAction;
use App\Domain\Auth\DTOs\UserData;
use App\Domain\Auth\Livewire\Users\UserIndex;
use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LockoutProtectionTest extends TestCase
{
    use RefreshDatabase;

    private User $soleSuperAdmin;

    private User $adminUser;

    private Role $adminRole;

    private Role $viewerRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->soleSuperAdmin = User::factory()->superAdmin()->create([
            'name' => 'Sole Super Admin',
            'email' => 'sole@example.com',
        ]);

        $this->adminRole = Role::factory()->admin()->create();
        $this->viewerRole = Role::factory()->create([
            'name' => 'Viewer',
            'slug' => 'viewer',
            'level' => 10,
            'is_system' => false,
        ]);

        $perms = [
            Permission::create(['name' => 'View Users', 'slug' => 'users.view']),
            Permission::create(['name' => 'Create Users', 'slug' => 'users.create']),
            Permission::create(['name' => 'Update Users', 'slug' => 'users.update']),
            Permission::create(['name' => 'Delete Users', 'slug' => 'users.delete']),
        ];
        $this->adminRole->permissions()->sync(collect($perms)->pluck('id'));

        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_super_admin' => false,
        ]);
        $this->adminUser->roles()->attach($this->adminRole);
    }

    public function test_cannot_delete_self_via_action(): void
    {
        $action = app(DeleteUserAction::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('You cannot delete your own account.');

        $action($this->adminUser, $this->adminUser);
    }

    public function test_cannot_delete_last_remaining_active_super_admin(): void
    {
        $action = app(DeleteUserAction::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot delete the last remaining active super administrator.');

        // AdminUser attempts deleting sole super admin
        $action($this->soleSuperAdmin, $this->adminUser);
    }

    public function test_can_delete_super_admin_if_another_active_super_admin_exists(): void
    {
        $secondSuperAdmin = User::factory()->superAdmin()->create();
        $action = app(DeleteUserAction::class);

        $result = $action($this->soleSuperAdmin, $secondSuperAdmin);

        $this->assertTrue($result);
        $this->assertTrue($this->soleSuperAdmin->fresh()->trashed());
    }

    public function test_cannot_demote_last_remaining_active_super_admin_via_update(): void
    {
        $action = app(UpdateUserAction::class);

        $data = new UserData(
            name: 'Sole Super Admin',
            email: 'sole@example.com',
            password: null,
            isSuperAdmin: false, // Attempting to demote
            roleIds: [$this->adminRole->id],
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('The system must have at least one active super administrator.');

        $action($this->soleSuperAdmin, $data, $this->soleSuperAdmin);
    }

    public function test_can_demote_super_admin_if_another_active_super_admin_exists(): void
    {
        User::factory()->superAdmin()->create();
        $action = app(UpdateUserAction::class);

        $data = new UserData(
            name: 'Demoted Super Admin',
            email: 'demoted@example.com',
            password: null,
            isSuperAdmin: false,
            roleIds: [$this->adminRole->id],
        );

        $updated = $action($this->soleSuperAdmin, $data, $this->soleSuperAdmin);

        $this->assertFalse($updated->fresh()->is_super_admin);
    }

    public function test_admin_user_cannot_self_demote_to_lose_panel_access(): void
    {
        $action = app(UpdateUserAction::class);

        // Admin user updating their own profile and removing all administrative roles
        $data = new UserData(
            name: 'Admin User',
            email: 'admin@example.com',
            password: null,
            isSuperAdmin: false,
            roleIds: [$this->viewerRole->id], // Demoting to non-admin viewer
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('You cannot remove your own administrative access or demote yourself from the management panel.');

        $action($this->adminUser, $data, $this->adminUser);
    }

    public function test_admin_user_can_update_own_profile_when_retaining_admin_role(): void
    {
        $action = app(UpdateUserAction::class);

        $data = new UserData(
            name: 'Updated Admin Name',
            email: 'admin.updated@example.com',
            password: null,
            isSuperAdmin: false,
            roleIds: [$this->adminRole->id], // Keeps admin role
        );

        $updated = $action($this->adminUser, $data, $this->adminUser);

        $this->assertEquals('Updated Admin Name', $updated->name);
        $this->assertEquals('admin.updated@example.com', $updated->email);
    }

    public function test_admin_user_can_remove_admin_roles_from_other_users(): void
    {
        $otherAdmin = User::factory()->create();
        $otherAdmin->roles()->attach($this->adminRole);

        $action = app(UpdateUserAction::class);

        // Admin user demotes OTHER admin to viewer
        $data = new UserData(
            name: 'Demoted Other User',
            email: $otherAdmin->email,
            password: null,
            isSuperAdmin: false,
            roleIds: [$this->viewerRole->id],
        );

        $updated = $action($otherAdmin, $data, $this->adminUser);

        $this->assertFalse($updated->fresh()->roles->contains($this->adminRole));
        $this->assertTrue($updated->fresh()->roles->contains($this->viewerRole));
    }

    public function test_user_index_livewire_catches_self_demotion_and_flashes_error(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(UserIndex::class)
            ->call('openEditModal', $this->adminUser->id)
            ->set('form.role_ids', [$this->viewerRole->id])
            ->call('save')
            ->assertSee('You cannot remove your own administrative access');

        // Verify role was not stripped
        $this->assertTrue($this->adminUser->fresh()->roles->contains($this->adminRole));
    }

    public function test_user_index_livewire_catches_last_super_admin_deletion(): void
    {
        $this->actingAs($this->soleSuperAdmin);

        Livewire::test(UserIndex::class)
            ->set('deletingUserId', $this->soleSuperAdmin->id)
            ->call('deleteUser')
            ->assertSee('You cannot delete your own account.');

        $this->assertFalse($this->soleSuperAdmin->fresh()->trashed());
    }
}
