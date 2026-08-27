<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Auth;

use App\Domain\Auth\Actions\CreateRoleAction;
use App\Domain\Auth\Actions\CreateUserAction;
use App\Domain\Auth\Actions\UpdateRoleAction;
use App\Domain\Auth\Actions\UpdateUserAction;
use App\Domain\Auth\DTOs\RoleData;
use App\Domain\Auth\DTOs\UserData;
use App\Domain\Auth\Enums\PermissionAuditAction;
use App\Domain\Auth\Livewire\AuditLogs\PermissionAuditLogIndex;
use App\Domain\Auth\Models\Department;
use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\PermissionAuditLog;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PermissionAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigning_a_role_records_who_granted_it_to_whom(): void
    {
        $actor = User::factory()->create(['name' => 'Alice Admin', 'email' => 'alice@example.com']);
        $target = User::factory()->create(['name' => 'Bob Target']);
        $role = Role::factory()->create(['name' => 'Editor']);

        $this->actingAs($actor);
        $target->roles()->attach($role);

        $log = PermissionAuditLog::query()->sole();

        $this->assertSame(PermissionAuditAction::Assigned, $log->action);
        $this->assertSame($actor->id, $log->actor_id);
        $this->assertSame('Alice Admin', $log->actor_name);
        $this->assertSame('alice@example.com', $log->actor_email);
        $this->assertSame(PermissionAuditLog::SUBJECT_USER, $log->subject_type);
        $this->assertSame($target->id, $log->subject_id);
        $this->assertSame('Bob Target', $log->subject_name);
        $this->assertSame(PermissionAuditLog::GRANTABLE_ROLE, $log->grantable_type);
        $this->assertSame($role->id, $log->grantable_id);
        $this->assertSame('Editor', $log->grantable_name);
        $this->assertNull($log->department_id);
        $this->assertNotNull($log->created_at);
    }

    public function test_removing_a_role_records_a_revocation(): void
    {
        $actor = User::factory()->create(['name' => 'Alice Admin']);
        $target = User::factory()->create(['name' => 'Bob Target']);
        $role = Role::factory()->create(['name' => 'Editor']);

        $target->roles()->attach($role);
        $this->assertSame(1, PermissionAuditLog::count());

        $this->actingAs($actor);
        $target->roles()->detach($role);

        $log = PermissionAuditLog::query()
            ->where('action', PermissionAuditAction::Revoked)
            ->sole();

        $this->assertSame(PermissionAuditAction::Revoked, $log->action);
        $this->assertSame($actor->id, $log->actor_id);
        $this->assertSame($target->id, $log->subject_id);
        $this->assertSame($role->id, $log->grantable_id);
    }

    public function test_syncing_roles_logs_only_attached_and_detached_ids(): void
    {
        $kept = Role::factory()->create(['name' => 'Kept']);
        $removed = Role::factory()->create(['name' => 'Removed']);
        $added = Role::factory()->create(['name' => 'Added']);
        $user = User::factory()->create();

        $user->roles()->attach([$kept->id, $removed->id]);
        $this->assertSame(2, PermissionAuditLog::count());

        $user->roles()->sync([$kept->id, $added->id]);

        $this->assertSame(4, PermissionAuditLog::count());
        $this->assertTrue(PermissionAuditLog::query()
            ->where('action', PermissionAuditAction::Revoked)
            ->where('grantable_id', $removed->id)
            ->exists());
        $this->assertTrue(PermissionAuditLog::query()
            ->where('action', PermissionAuditAction::Assigned)
            ->where('grantable_id', $added->id)
            ->exists());
        $this->assertSame(1, PermissionAuditLog::query()->where('grantable_id', $kept->id)->count());
    }

    public function test_assigning_and_removing_a_permission_on_a_role_is_logged(): void
    {
        $actor = User::factory()->create(['name' => 'Alice Admin']);
        $role = Role::factory()->create(['name' => 'Editor']);
        $permission = Permission::factory()->create(['slug' => 'posts.publish']);

        $this->actingAs($actor);
        $role->permissions()->attach($permission);

        $assigned = PermissionAuditLog::query()->sole();
        $this->assertSame(PermissionAuditAction::Assigned, $assigned->action);
        $this->assertSame(PermissionAuditLog::SUBJECT_ROLE, $assigned->subject_type);
        $this->assertSame($role->id, $assigned->subject_id);
        $this->assertSame('Editor', $assigned->subject_name);
        $this->assertSame(PermissionAuditLog::GRANTABLE_PERMISSION, $assigned->grantable_type);
        $this->assertSame($permission->id, $assigned->grantable_id);
        $this->assertSame('posts.publish', $assigned->grantable_name);
        $this->assertSame($actor->id, $assigned->actor_id);

        $role->permissions()->detach($permission);

        $revoked = PermissionAuditLog::query()
            ->where('action', PermissionAuditAction::Revoked)
            ->sole();
        $this->assertSame(PermissionAuditAction::Revoked, $revoked->action);
        $this->assertSame($permission->id, $revoked->grantable_id);
        $this->assertSame($actor->id, $revoked->actor_id);
    }

    public function test_unauthenticated_changes_are_logged_without_an_actor(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $user->roles()->attach($role);

        $log = PermissionAuditLog::query()->sole();
        $this->assertNull($log->actor_id);
        $this->assertNull($log->actor_name);
        $this->assertSame(PermissionAuditAction::Assigned, $log->action);
    }

    public function test_departmental_role_assignment_is_logged_with_department(): void
    {
        $actor = User::factory()->create(['name' => 'Alice Admin']);
        $target = User::factory()->create(['name' => 'Bob Target']);
        $role = Role::factory()->create(['name' => 'Manager']);
        $department = Department::factory()->create(['name' => 'Finance']);

        $this->actingAs($actor);
        $target->syncDepartments([
            [
                'department_id' => $department->id,
                'role_id' => $role->id,
                'is_primary' => true,
            ],
        ]);

        $log = PermissionAuditLog::query()->sole();
        $this->assertSame(PermissionAuditAction::Assigned, $log->action);
        $this->assertSame($actor->id, $log->actor_id);
        $this->assertSame($target->id, $log->subject_id);
        $this->assertSame($role->id, $log->grantable_id);
        $this->assertSame($department->id, $log->department_id);
        $this->assertSame('Finance', $log->department_name);
    }

    public function test_changing_a_departmental_role_logs_revocation_and_assignment(): void
    {
        $target = User::factory()->create();
        $manager = Role::factory()->create(['name' => 'Manager']);
        $operator = Role::factory()->create(['name' => 'Operator']);
        $department = Department::factory()->create(['name' => 'Ops']);

        $target->syncDepartments([
            [
                'department_id' => $department->id,
                'role_id' => $manager->id,
                'is_primary' => true,
            ],
        ]);

        $this->assertSame(1, PermissionAuditLog::count());

        $target->syncDepartments([
            [
                'department_id' => $department->id,
                'role_id' => $operator->id,
                'is_primary' => true,
            ],
        ]);

        $this->assertSame(3, PermissionAuditLog::count());
        $this->assertTrue(PermissionAuditLog::query()
            ->where('action', PermissionAuditAction::Revoked)
            ->where('grantable_id', $manager->id)
            ->where('department_id', $department->id)
            ->exists());
        $this->assertTrue(PermissionAuditLog::query()
            ->where('action', PermissionAuditAction::Assigned)
            ->where('grantable_id', $operator->id)
            ->where('department_id', $department->id)
            ->exists());
    }

    public function test_department_membership_without_a_role_is_not_logged(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();

        $user->syncDepartments([
            [
                'department_id' => $department->id,
                'role_id' => null,
                'is_primary' => true,
            ],
        ]);

        $this->assertSame(0, PermissionAuditLog::count());
    }

    public function test_audit_logs_are_immutable(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $user->roles()->attach($role);

        $log = PermissionAuditLog::query()->sole();

        try {
            $log->update(['actor_name' => 'Tampered']);
            $this->fail('Audit logs must not accept updates.');
        } catch (DomainException $exception) {
            $this->assertSame(__('Permission audit logs cannot be modified.'), $exception->getMessage());
        }

        $this->assertSame($user->name, $log->fresh()->subject_name);

        try {
            $log->delete();
            $this->fail('Audit logs must not accept deletes.');
        } catch (DomainException $exception) {
            $this->assertSame(__('Permission audit logs cannot be deleted.'), $exception->getMessage());
        }

        $this->assertSame(1, PermissionAuditLog::count());
    }

    public function test_user_and_role_actions_populate_the_audit_log(): void
    {
        $actor = User::factory()->superAdmin()->create(['name' => 'Alice Admin']);
        $this->actingAs($actor);

        $role = app(CreateRoleAction::class)(new RoleData(
            name: 'Editor',
            slug: 'editor',
            permissionIds: [Permission::factory()->create(['slug' => 'posts.publish'])->id],
        ));

        $this->assertTrue(PermissionAuditLog::query()
            ->where('action', PermissionAuditAction::Assigned)
            ->where('subject_id', $role->id)
            ->where('grantable_name', 'posts.publish')
            ->exists());

        $createdUser = app(CreateUserAction::class)(new UserData(
            name: 'Bob Target',
            email: 'bob@example.com',
            password: 'secret-password',
            roleIds: [$role->id],
        ));

        $this->assertTrue(PermissionAuditLog::query()
            ->where('action', PermissionAuditAction::Assigned)
            ->where('subject_id', $createdUser->id)
            ->where('grantable_id', $role->id)
            ->where('actor_id', $actor->id)
            ->exists());

        $replacement = Permission::factory()->create(['slug' => 'posts.edit']);
        app(UpdateRoleAction::class)($role, new RoleData(
            name: 'Editor',
            slug: 'editor',
            permissionIds: [$replacement->id],
        ));

        $this->assertTrue(PermissionAuditLog::query()
            ->where('action', PermissionAuditAction::Revoked)
            ->where('subject_id', $role->id)
            ->where('grantable_name', 'posts.publish')
            ->exists());
        $this->assertTrue(PermissionAuditLog::query()
            ->where('action', PermissionAuditAction::Assigned)
            ->where('subject_id', $role->id)
            ->where('grantable_name', 'posts.edit')
            ->exists());

        app(UpdateUserAction::class)($createdUser, new UserData(
            name: 'Bob Target',
            email: 'bob@example.com',
            roleIds: [],
        ), $actor);

        $this->assertTrue(PermissionAuditLog::query()
            ->where('action', PermissionAuditAction::Revoked)
            ->where('subject_id', $createdUser->id)
            ->where('grantable_id', $role->id)
            ->where('actor_id', $actor->id)
            ->exists());
    }

    public function test_unauthorized_users_cannot_view_audit_logs(): void
    {
        $this->get(route('admin.audit-logs.index'))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();
    }

    public function test_authorized_users_can_search_the_audit_trail(): void
    {
        $viewer = User::factory()->superAdmin()->create();
        PermissionAuditLog::factory()->create([
            'actor_name' => 'Alice Admin',
            'subject_name' => 'Bob Target',
            'grantable_name' => 'Editor',
            'action' => PermissionAuditAction::Assigned,
        ]);
        PermissionAuditLog::factory()->revoked()->create([
            'actor_name' => 'Carol Ops',
            'subject_name' => 'Dave User',
            'grantable_name' => 'Viewer',
        ]);

        $this->actingAs($viewer)
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('Permission Audit Logs')
            ->assertSee('Alice Admin')
            ->assertSee('Bob Target')
            ->assertSee('Editor');

        Livewire::actingAs($viewer)
            ->test(PermissionAuditLogIndex::class)
            ->assertSee('Alice Admin')
            ->assertSee('Carol Ops')
            ->set('search', 'Alice')
            ->assertSee('Alice Admin')
            ->assertDontSee('Carol Ops')
            ->set('search', '')
            ->set('actionFilter', 'revoked')
            ->assertSee('Carol Ops')
            ->assertDontSee('Alice Admin');
    }
}
