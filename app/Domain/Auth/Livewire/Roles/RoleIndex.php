<?php

declare(strict_types=1);

namespace App\Domain\Auth\Livewire\Roles;

use App\Domain\Auth\Actions\CreateRoleAction;
use App\Domain\Auth\Actions\DeleteRoleAction;
use App\Domain\Auth\Actions\RestoreRoleAction;
use App\Domain\Auth\Actions\UpdateRoleAction;
use App\Domain\Auth\Livewire\Forms\RoleForm;
use App\Domain\Auth\Models\Department;
use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use DomainException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Roles')]
class RoleIndex extends Component
{
    use WithPagination;

    public RoleForm $form;

    public string $search = '';

    public bool $showDeleted = false;

    public bool $showingModal = false;

    public bool $showingDeleteModal = false;

    public ?string $deletingRoleId = null;

    public ?string $deletingRoleName = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Role::class);
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
        $this->authorize('create', Role::class);
        $this->form->reset();
        $this->resetValidation();
        $this->showingModal = true;
    }

    public function openEditModal(string $id): void
    {
        $role = Role::withTrashed()->with('departments')->findOrFail($id);
        $this->authorize('update', $role);

        if ($role->isSystem()) {
            session()->flash('error', __('System roles cannot be edited.'));

            return;
        }

        $this->form->setRole($role);
        $this->resetValidation();
        $this->showingModal = true;
    }

    public function save(CreateRoleAction $createAction, UpdateRoleAction $updateAction): void
    {
        $this->form->validate();

        if ($this->form->id !== null) {
            $role = Role::withTrashed()->findOrFail($this->form->id);
            $this->authorize('update', $role);

            try {
                $updateAction($role, $this->form->toDTO());
                session()->flash('status', __('Role updated successfully.'));
            } catch (DomainException $e) {
                session()->flash('error', $e->getMessage());

                return;
            }
        } else {
            $this->authorize('create', Role::class);

            $createAction($this->form->toDTO());
            session()->flash('status', __('Role created successfully.'));
        }

        $this->showingModal = false;
        $this->form->reset();
    }

    public function confirmDelete(string $id): void
    {
        $role = Role::findOrFail($id);
        $this->authorize('delete', $role);

        if ($role->isSystem()) {
            session()->flash('error', __('System roles cannot be deleted.'));

            return;
        }

        $this->deletingRoleId = $role->id;
        $this->deletingRoleName = $role->name;
        $this->showingDeleteModal = true;
    }

    public function deleteRole(DeleteRoleAction $deleteAction): void
    {
        if ($this->deletingRoleId === null) {
            return;
        }

        $role = Role::findOrFail($this->deletingRoleId);
        $this->authorize('delete', $role);

        try {
            $deleteAction($role);
            session()->flash('status', __('Role deleted successfully.'));
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->showingDeleteModal = false;
        $this->deletingRoleId = null;
        $this->deletingRoleName = null;
    }

    public function restoreRole(string $id, RestoreRoleAction $restoreAction): void
    {
        $role = Role::withTrashed()->findOrFail($id);
        $this->authorize('restore', $role);

        $restoreAction($role);
        session()->flash('status', __('Role restored successfully.'));
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'showDeleted']);
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Role::query()->withCount('permissions', 'users');

        if ($this->showDeleted) {
            $query->onlyTrashed();
        }

        if (filled($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('slug', 'like', '%'.$this->search.'%');
            });
        }

        $roles = $query->with('departments')->orderByLevel()->paginate(10);

        $stats = [
            'total' => Role::withTrashed()->count(),
            'active' => Role::withoutTrashed()->count(),
            'total_permissions' => Permission::withoutTrashed()->count(),
            'trashed' => Role::onlyTrashed()->count(),
        ];

        $availableDepartments = Department::where('is_active', true)->orderBy('name')->get();

        return view('domain.auth.roles.index', [
            'roles' => $roles,
            'stats' => $stats,
            'availableDepartments' => $availableDepartments,
        ]);

    }
}
