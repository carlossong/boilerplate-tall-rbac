<?php

declare(strict_types=1);

namespace App\Domain\Auth\Livewire\Departments;

use App\Domain\Auth\Actions\CreateDepartmentAction;
use App\Domain\Auth\Actions\DeleteDepartmentAction;
use App\Domain\Auth\Actions\RestoreDepartmentAction;
use App\Domain\Auth\Actions\UpdateDepartmentAction;
use App\Domain\Auth\Livewire\Forms\DepartmentForm;
use App\Domain\Auth\Models\Department;
use App\Domain\Auth\Models\Role;
use DomainException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Departments')]
class DepartmentIndex extends Component
{
    use WithPagination;

    public DepartmentForm $form;

    public string $search = '';

    public bool $showDeleted = false;

    public bool $showingModal = false;

    public bool $showingDeleteModal = false;

    public ?string $deletingDepartmentId = null;

    public ?string $deletingDepartmentName = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Department::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedShowDeleted(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Department::class);
        $this->form->reset();
        $this->form->is_active = true;
        $this->resetValidation();
        $this->showingModal = true;
    }

    public function openEditModal(string $id): void
    {
        $department = Department::withTrashed()->with('roles')->findOrFail($id);
        $this->authorize('update', $department);

        $this->form->setDepartment($department);
        $this->resetValidation();
        $this->showingModal = true;
    }

    public function save(CreateDepartmentAction $createAction, UpdateDepartmentAction $updateAction): void
    {
        $this->form->validate();

        if ($this->form->id !== null) {
            $department = Department::withTrashed()->findOrFail($this->form->id);
            $this->authorize('update', $department);

            $updateAction($department, $this->form->toDTO());
            session()->flash('status', __('Department updated successfully.'));
        } else {
            $this->authorize('create', Department::class);

            $createAction($this->form->toDTO());
            session()->flash('status', __('Department created successfully.'));
        }

        $this->showingModal = false;
        $this->form->reset();
    }

    public function confirmDelete(string $id): void
    {
        $department = Department::findOrFail($id);
        $this->authorize('delete', $department);

        $this->deletingDepartmentId = $department->id;
        $this->deletingDepartmentName = $department->name;
        $this->showingDeleteModal = true;
    }

    public function deleteDepartment(DeleteDepartmentAction $deleteAction): void
    {
        if ($this->deletingDepartmentId === null) {
            return;
        }

        $department = Department::findOrFail($this->deletingDepartmentId);
        $this->authorize('delete', $department);

        try {
            $deleteAction($department);
            session()->flash('status', __('Department deleted successfully.'));
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->showingDeleteModal = false;
        $this->deletingDepartmentId = null;
        $this->deletingDepartmentName = null;
    }

    public function restoreDepartment(string $id, RestoreDepartmentAction $restoreAction): void
    {
        $department = Department::withTrashed()->findOrFail($id);
        $this->authorize('restore', $department);

        $restoreAction($department);
        session()->flash('status', __('Department restored successfully.'));
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'showDeleted']);
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Department::query()
            ->with('roles')
            ->withCount('users')
            ->when($this->showDeleted, fn ($q) => $q->onlyTrashed(), fn ($q) => $q->withoutTrashed())
            ->when(
                filled($this->search),
                fn ($q) => $q->where(function ($sub) {
                    $sub->where('name', 'like', "%{$this->search}%")
                        ->orWhere('slug', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                })
            )
            ->orderBy('name');

        $departments = $query->paginate(10);

        $stats = [
            'total' => Department::count(),
            'active' => Department::active()->count(),
            'archived' => Department::onlyTrashed()->count(),
        ];

        $availableRoles = Role::query()->orderByLevel()->get();

        return view('domain.auth.departments.index', [
            'departments' => $departments,
            'stats' => $stats,
            'availableRoles' => $availableRoles,
        ]);
    }
}
