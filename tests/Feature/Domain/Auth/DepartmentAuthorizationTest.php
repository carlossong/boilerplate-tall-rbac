<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Auth;

use App\Domain\Auth\Models\Department;
use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DepartmentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_role_in_specific_department(): void
    {
        $financialDept = Department::factory()->create(['name' => 'Finance', 'slug' => 'finance']);
        $opsDept = Department::factory()->create(['name' => 'Operations', 'slug' => 'operations']);

        $managerRole = Role::factory()->create(['name' => 'Manager', 'slug' => 'manager', 'level' => 50]);
        $operatorRole = Role::factory()->create(['name' => 'Operator', 'slug' => 'operator', 'level' => 20]);

        $user = User::factory()->create();

        // Assign Manager in Financial, Operator in Operational
        $user->departments()->attach([
            $financialDept->id => ['id' => (string) Str::uuid(), 'role_id' => $managerRole->id, 'is_primary' => true],
            $opsDept->id => ['id' => (string) Str::uuid(), 'role_id' => $operatorRole->id, 'is_primary' => false],
        ]);

        $this->assertTrue($user->hasRoleInDepartment('manager', $financialDept));
        $this->assertFalse($user->hasRoleInDepartment('operator', $financialDept));

        $this->assertTrue($user->hasRoleInDepartment('operator', $opsDept));
        $this->assertFalse($user->hasRoleInDepartment('manager', $opsDept));
    }

    public function test_user_has_permission_in_department_via_sector_role(): void
    {
        $financialDept = Department::factory()->create(['slug' => 'finance']);
        $opsDept = Department::factory()->create(['slug' => 'operations']);

        $payPermission = Permission::factory()->create(['slug' => 'expenses.pay']);
        $drivePermission = Permission::factory()->create(['slug' => 'trips.drive']);

        $financeRole = Role::factory()->create(['level' => 50]);
        $financeRole->permissions()->attach($payPermission);

        $opsRole = Role::factory()->create(['level' => 20]);
        $opsRole->permissions()->attach($drivePermission);

        $user = User::factory()->create();

        $user->departments()->attach([
            $financialDept->id => ['id' => (string) Str::uuid(), 'role_id' => $financeRole->id, 'is_primary' => true],
            $opsDept->id => ['id' => (string) Str::uuid(), 'role_id' => $opsRole->id, 'is_primary' => false],
        ]);

        // In Financial, can pay expenses but cannot drive trips
        $this->assertTrue($user->hasPermissionInDepartment('expenses.pay', $financialDept));
        $this->assertFalse($user->hasPermissionInDepartment('trips.drive', $financialDept));

        // In Operations, can drive trips but cannot pay expenses
        $this->assertTrue($user->hasPermissionInDepartment('trips.drive', $opsDept));
        $this->assertFalse($user->hasPermissionInDepartment('expenses.pay', $opsDept));
    }

    public function test_highest_role_level_resolves_correctly_per_department(): void
    {
        $financialDept = Department::factory()->create();
        $opsDept = Department::factory()->create();

        $financeRole = Role::factory()->create(['level' => 70]);
        $opsRole = Role::factory()->create(['level' => 25]);

        $user = User::factory()->create();

        $user->departments()->attach([
            $financialDept->id => ['id' => (string) Str::uuid(), 'role_id' => $financeRole->id, 'is_primary' => true],
            $opsDept->id => ['id' => (string) Str::uuid(), 'role_id' => $opsRole->id, 'is_primary' => false],
        ]);

        // Department-scoped highest level
        $this->assertSame(70, $user->highestRoleLevel($financialDept));
        $this->assertSame(25, $user->highestRoleLevel($opsDept));

        // Global highest level considers all
        $this->assertSame(70, $user->highestRoleLevel());
    }

    public function test_primary_department_is_resolved(): void
    {
        $dept1 = Department::factory()->create(['name' => 'Dept One']);
        $dept2 = Department::factory()->create(['name' => 'Dept Two']);

        $user = User::factory()->create();

        $user->departments()->attach([
            $dept1->id => ['id' => (string) Str::uuid(), 'role_id' => null, 'is_primary' => false],
            $dept2->id => ['id' => (string) Str::uuid(), 'role_id' => null, 'is_primary' => true],
        ]);

        $primary = $user->primaryDepartment();
        $this->assertNotNull($primary);
        $this->assertSame($dept2->id, $primary->id);
    }
}
