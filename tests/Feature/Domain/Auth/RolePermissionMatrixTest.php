<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Auth;

use App\Domain\Auth\Enums\PermissionAuditAction;
use App\Domain\Auth\Livewire\Roles\RoleIndex;
use App\Domain\Auth\Livewire\Roles\RolePermissionMatrix;
use App\Domain\Auth\Livewire\Users\UserIndex;
use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\PermissionAuditLog;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RolePermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Role $editorRole;

    protected Permission $exportPermission;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create([
            'name' => 'Administrator',
            'slug' => 'admin',
            'level' => 80,
        ]);
        $adminRole->permissions()->sync(collect([
            Permission::create(['name' => 'View Users', 'slug' => 'users.view']),
            Permission::create(['name' => 'View Roles', 'slug' => 'roles.view']),
            Permission::create(['name' => 'Update Roles', 'slug' => 'roles.update']),
        ])->pluck('id'));

        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($adminRole);

        $this->editorRole = Role::create([
            'name' => 'Editor',
            'slug' => 'editor',
            'level' => 20,
        ]);
        $this->exportPermission = Permission::create([
            'name' => 'Export Reports',
            'slug' => 'reports.export',
            'group' => 'billing',
        ]);
    }

    public function test_unauthorized_user_cannot_access_the_matrix(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.roles.matrix'))
            ->assertForbidden();
    }

    public function test_authorized_user_can_view_the_matrix(): void
    {
        $this->actingAs($this->adminUser);

        $this->get(route('admin.roles.matrix'))
            ->assertOk()
            ->assertSee('Permission Matrix')
            ->assertSee('Editor')
            ->assertSee('Export Reports')
            ->assertSee('Lvl 20')
            ->assertSee('Lvl 80');
    }

    public function test_toggling_a_cell_grants_and_revokes_the_permission(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(RolePermissionMatrix::class)
            ->assertSee('Export Reports')
            ->call('toggle', $this->editorRole->id, $this->exportPermission->id)
            ->assertHasNoErrors();

        $this->assertTrue($this->editorRole->fresh()->permissions->contains($this->exportPermission));

        Livewire::test(RolePermissionMatrix::class)
            ->call('toggle', $this->editorRole->id, $this->exportPermission->id)
            ->assertHasNoErrors();

        $this->assertFalse($this->editorRole->fresh()->permissions->contains($this->exportPermission));
    }

    public function test_toggling_a_cell_is_audited(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(RolePermissionMatrix::class)
            ->call('toggle', $this->editorRole->id, $this->exportPermission->id);

        $log = PermissionAuditLog::query()
            ->where('subject_id', $this->editorRole->id)
            ->where('grantable_id', $this->exportPermission->id)
            ->sole();
        $this->assertSame(PermissionAuditAction::Assigned, $log->action);
        $this->assertSame($this->adminUser->id, $log->actor_id);
        $this->assertSame($this->editorRole->id, $log->subject_id);
        $this->assertSame($this->exportPermission->id, $log->grantable_id);
    }

    public function test_system_roles_cannot_be_toggled(): void
    {
        $this->actingAs($this->adminUser);

        $adminRole = Role::query()->where('slug', 'admin')->firstOrFail();

        Livewire::test(RolePermissionMatrix::class)
            ->call('toggle', $adminRole->id, $this->exportPermission->id)
            ->assertForbidden();

        $this->assertFalse($adminRole->fresh()->permissions->contains($this->exportPermission));
    }

    public function test_viewer_can_see_the_matrix_but_cannot_toggle(): void
    {
        $viewerRole = Role::create(['name' => 'Viewer', 'slug' => 'viewer', 'level' => 10]);
        $viewerRole->permissions()->sync(
            Permission::query()->where('slug', 'roles.view')->pluck('id'),
        );
        $viewer = User::factory()->create();
        $viewer->roles()->attach($viewerRole);

        $this->actingAs($viewer);

        $this->get(route('admin.roles.matrix'))
            ->assertOk();

        Livewire::test(RolePermissionMatrix::class)
            ->call('toggle', $this->editorRole->id, $this->exportPermission->id)
            ->assertForbidden();
    }

    public function test_editing_a_role_does_not_clear_assigned_permissions(): void
    {
        $this->actingAs($this->adminUser);
        $this->editorRole->permissions()->attach($this->exportPermission);

        Livewire::test(RoleIndex::class)
            ->call('openEditModal', $this->editorRole->id)
            ->set('form.name', 'Senior Editor')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Senior Editor', $this->editorRole->fresh()->name);
        $this->assertTrue($this->editorRole->fresh()->permissions->contains($this->exportPermission));
    }

    public function test_user_listing_shows_role_level_badges(): void
    {
        $this->adminUser->roles()->attach($this->editorRole);

        $this->actingAs($this->adminUser);

        Livewire::test(UserIndex::class)
            ->assertSee('Lvl 80')
            ->assertSee('Lvl 20');
    }
}
