<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Auth;

use App\Domain\Auth\Actions\CreateUserAction;
use App\Domain\Auth\Actions\UpdateUserAction;
use App\Domain\Auth\DTOs\UserData;
use App\Domain\Auth\Enums\PermissionAuditAction;
use App\Domain\Auth\Livewire\Users\UserIndex;
use App\Domain\Auth\Models\Department;
use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\PermissionAuditLog;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DirectPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_permission_grants_access_without_a_role(): void
    {
        $permission = Permission::factory()->create(['slug' => 'reports.export']);
        $user = User::factory()->create();

        $this->assertFalse($user->hasPermissionTo('reports.export'));
        $this->assertFalse($user->can('reports.export'));

        $user->permissions()->attach($permission);

        $this->assertTrue($user->hasPermissionTo('reports.export'));
        $this->assertTrue($user->can('reports.export'));
    }

    public function test_direct_permission_applies_inside_department_scope(): void
    {
        $department = Department::factory()->create();
        $permission = Permission::factory()->create(['slug' => 'expenses.pay']);
        $user = User::factory()->create();

        $user->departments()->attach($department, [
            'id' => (string) Str::uuid(),
            'role_id' => null,
            'is_primary' => true,
        ]);
        $user->permissions()->attach($permission);

        $this->assertTrue($user->hasPermissionInDepartment('expenses.pay', $department));
        $this->assertTrue($user->hasPermissionTo('expenses.pay', $department));
    }

    public function test_detaching_a_direct_permission_invalidates_the_user_cache(): void
    {
        $permission = Permission::factory()->create(['slug' => 'reports.export']);
        $user = User::factory()->create();
        $user->permissions()->attach($permission);

        $this->assertTrue($user->hasPermissionTo('reports.export'));

        $user->permissions()->detach($permission);

        $this->assertFalse($user->hasPermissionTo('reports.export'));
        $this->assertFalse($user->fresh()->hasPermissionTo('reports.export'));
    }

    public function test_assigning_and_revoking_a_direct_permission_is_audited(): void
    {
        $actor = User::factory()->create(['name' => 'Alice Admin']);
        $target = User::factory()->create(['name' => 'Bob Target']);
        $permission = Permission::factory()->create(['slug' => 'reports.export']);

        $this->actingAs($actor);
        $target->permissions()->attach($permission);

        $assigned = PermissionAuditLog::query()->sole();
        $this->assertSame(PermissionAuditAction::Assigned, $assigned->action);
        $this->assertSame($actor->id, $assigned->actor_id);
        $this->assertSame(PermissionAuditLog::SUBJECT_USER, $assigned->subject_type);
        $this->assertSame($target->id, $assigned->subject_id);
        $this->assertSame(PermissionAuditLog::GRANTABLE_PERMISSION, $assigned->grantable_type);
        $this->assertSame($permission->id, $assigned->grantable_id);
        $this->assertSame('reports.export', $assigned->grantable_name);

        $target->permissions()->detach($permission);

        $revoked = PermissionAuditLog::query()
            ->where('action', PermissionAuditAction::Revoked)
            ->sole();
        $this->assertSame($target->id, $revoked->subject_id);
        $this->assertSame($permission->id, $revoked->grantable_id);
    }

    public function test_user_actions_sync_direct_permissions(): void
    {
        $permission = Permission::factory()->create(['slug' => 'reports.export']);

        $user = app(CreateUserAction::class)(new UserData(
            name: 'Exception User',
            email: 'exception@example.com',
            password: 'secret-password',
            permissionIds: [$permission->id],
        ));

        $this->assertTrue($user->fresh()->hasPermissionTo('reports.export'));

        app(UpdateUserAction::class)($user, new UserData(
            name: 'Exception User',
            email: 'exception@example.com',
            permissionIds: [],
        ));

        $this->assertFalse($user->fresh()->hasPermissionTo('reports.export'));
    }

    public function test_user_form_can_assign_a_direct_permission(): void
    {
        $adminRole = Role::factory()->create(['slug' => 'admin', 'level' => 80]);
        $perms = [
            Permission::create(['name' => 'View Users', 'slug' => 'users.view']),
            Permission::create(['name' => 'Create Users', 'slug' => 'users.create']),
            Permission::create(['name' => 'Update Users', 'slug' => 'users.update']),
            Permission::create(['name' => 'Delete Users', 'slug' => 'users.delete']),
        ];
        $adminRole->permissions()->sync(collect($perms)->pluck('id'));

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $exception = Permission::factory()->create([
            'name' => 'Export Reports',
            'slug' => 'reports.export',
            'group' => 'billing',
        ]);
        $target = User::factory()->create();

        $this->actingAs($admin);

        Livewire::test(UserIndex::class)
            ->call('openEditModal', $target->id)
            ->set('form.permission_ids', [$exception->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($target->fresh()->hasPermissionTo('reports.export'));
        $this->assertTrue($target->permissions->contains($exception));
    }

    public function test_assignment_ui_groups_by_group_column_not_slug_prefix(): void
    {
        Permission::factory()->create([
            'name' => 'Export Reports',
            'slug' => 'reports.export',
            'group' => 'billing',
        ]);

        $grouped = Permission::groupedForAssignment();

        $this->assertTrue($grouped->has('billing'));
        $this->assertFalse($grouped->has('reports'));
        $this->assertSame('reports.export', $grouped->get('billing')->first()?->slug);
    }
}
