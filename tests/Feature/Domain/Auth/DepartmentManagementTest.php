<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Auth;

use App\Domain\Auth\Livewire\Departments\DepartmentIndex;
use App\Domain\Auth\Models\Department;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->superAdmin()->create();
    }

    public function test_guests_cannot_access_departments(): void
    {
        $this->get(route('admin.departments.index'))
            ->assertRedirect(route('login'));
    }

    public function test_users_without_permission_cannot_access_departments(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.departments.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_departments_index(): void
    {
        Department::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.departments.index'))
            ->assertOk()
            ->assertSeeLivewire(DepartmentIndex::class);
    }

    public function test_can_create_department(): void
    {
        Livewire::actingAs($this->admin)
            ->test(DepartmentIndex::class)
            ->call('openCreateModal')
            ->set('form.name', 'Central Finance')
            ->set('form.slug', 'central-finance')
            ->set('form.description', 'Sector responsible for treasury and disbursements.')
            ->set('form.is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('departments', [
            'name' => 'Central Finance',
            'slug' => 'central-finance',
            'is_active' => true,
        ]);
    }

    public function test_can_update_department(): void
    {
        $department = Department::factory()->create([
            'name' => 'Old Logistics',
            'slug' => 'old-logistics',
        ]);

        Livewire::actingAs($this->admin)
            ->test(DepartmentIndex::class)
            ->call('openEditModal', $department->id)
            ->set('form.name', 'Logistics & Fleet')
            ->set('form.slug', 'logistics-fleet')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'Logistics & Fleet',
            'slug' => 'logistics-fleet',
        ]);
    }

    public function test_can_soft_delete_and_restore_department(): void
    {
        $department = Department::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(DepartmentIndex::class)
            ->call('confirmDelete', $department->id)
            ->call('deleteDepartment');

        $this->assertSoftDeleted('departments', ['id' => $department->id]);

        Livewire::actingAs($this->admin)
            ->test(DepartmentIndex::class)
            ->call('restoreDepartment', $department->id);

        $this->assertNotSoftDeleted('departments', ['id' => $department->id]);
    }

    public function test_department_validation_enforces_unique_slug(): void
    {
        Department::factory()->create(['slug' => 'rh-global']);

        Livewire::actingAs($this->admin)
            ->test(DepartmentIndex::class)
            ->call('openCreateModal')
            ->set('form.name', 'Outro RH')
            ->set('form.slug', 'rh-global')
            ->call('save')
            ->assertHasErrors(['form.slug']);
    }
}
