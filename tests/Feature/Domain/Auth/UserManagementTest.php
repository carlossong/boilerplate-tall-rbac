<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Auth;

use App\Domain\Auth\Livewire\Users\UserIndex;
use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $perms = [
            Permission::create(['name' => 'View Users', 'slug' => 'users.view']),
            Permission::create(['name' => 'Create Users', 'slug' => 'users.create']),
            Permission::create(['name' => 'Update Users', 'slug' => 'users.update']),
            Permission::create(['name' => 'Delete Users', 'slug' => 'users.delete']),
        ];

        $this->adminRole->permissions()->sync(collect($perms)->pluck('id'));

        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($this->adminRole);
    }

    public function test_unauthorized_user_cannot_access_user_index(): void
    {
        $unauthorized = User::factory()->create();

        $this->actingAs($unauthorized);

        $this->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_authorized_user_can_access_user_index(): void
    {
        $this->actingAs($this->adminUser);

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('User Management');
    }

    public function test_can_search_and_list_users(): void
    {
        $this->actingAs($this->adminUser);

        $targetUser = User::factory()->create(['name' => 'Alice Wonder', 'email' => 'alice@example.com']);
        $otherUser = User::factory()->create(['name' => 'Bob Builder', 'email' => 'bob@example.com']);

        Livewire::test(UserIndex::class)
            ->assertSee('Alice Wonder')
            ->assertSee('Bob Builder')
            ->set('search', 'Alice')
            ->assertSee('Alice Wonder')
            ->assertDontSee('Bob Builder');
    }

    public function test_authorized_user_can_create_user(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(UserIndex::class)
            ->call('openCreateModal')
            ->assertSet('showingModal', true)
            ->set('form.name', 'New Guy')
            ->set('form.email', 'newguy@example.com')
            ->set('form.password', 'password123')
            ->set('form.role_ids', [$this->adminRole->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showingModal', false);

        $created = User::where('email', 'newguy@example.com')->first();
        $this->assertNotNull($created);
        $this->assertEquals('New Guy', $created->name);
        $this->assertTrue($created->roles->contains($this->adminRole));
    }

    public function test_authorized_user_can_update_user(): void
    {
        $this->actingAs($this->adminUser);

        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

        Livewire::test(UserIndex::class)
            ->call('openEditModal', $user->id)
            ->assertSet('showingModal', true)
            ->assertSet('form.name', 'Old Name')
            ->set('form.name', 'New Name')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('New Name', $user->fresh()->name);
    }

    public function test_authorized_user_can_soft_delete_and_restore_user(): void
    {
        $this->actingAs($this->adminUser);

        $user = User::factory()->create(['name' => 'Delete Me']);

        Livewire::test(UserIndex::class)
            ->call('confirmDelete', $user->id)
            ->assertSet('showingDeleteModal', true)
            ->call('deleteUser')
            ->assertSet('showingDeleteModal', false);

        $this->assertTrue($user->fresh()->trashed());

        Livewire::test(UserIndex::class)
            ->set('showDeleted', true)
            ->call('restoreUser', $user->id);

        $this->assertFalse($user->fresh()->trashed());
    }
}
