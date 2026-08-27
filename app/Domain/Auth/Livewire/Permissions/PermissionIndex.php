<?php

declare(strict_types=1);

namespace App\Domain\Auth\Livewire\Permissions;

use App\Domain\Auth\Actions\CreatePermissionAction;
use App\Domain\Auth\Actions\DeletePermissionAction;
use App\Domain\Auth\Actions\RestorePermissionAction;
use App\Domain\Auth\Actions\UpdatePermissionAction;
use App\Domain\Auth\Livewire\Forms\PermissionForm;
use App\Domain\Auth\Models\Permission;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Permissions')]
class PermissionIndex extends Component
{
    use WithPagination;

    public PermissionForm $form;

    public string $search = '';

    public string $resourceFilter = '';

    public bool $showDeleted = false;

    public bool $showingModal = false;

    public bool $showingDeleteModal = false;

    public ?string $deletingPermissionId = null;

    public ?string $deletingPermissionName = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Permission::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedResourceFilter(): void
    {
        $this->resetPage();
    }

    public function updatedShowDeleted(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Permission::class);
        $this->form->reset();
        $this->resetValidation();
        $this->showingModal = true;
    }

    public function openEditModal(string $id): void
    {
        $permission = Permission::withTrashed()->findOrFail($id);
        $this->authorize('update', $permission);

        $this->form->setPermission($permission);
        $this->resetValidation();
        $this->showingModal = true;
    }

    public function save(CreatePermissionAction $createAction, UpdatePermissionAction $updateAction): void
    {
        $this->form->validate();

        if ($this->form->id !== null) {
            $permission = Permission::withTrashed()->findOrFail($this->form->id);
            $this->authorize('update', $permission);

            $updateAction($permission, $this->form->toDTO());
            session()->flash('status', __('Permission updated successfully.'));
        } else {
            $this->authorize('create', Permission::class);

            $createAction($this->form->toDTO());
            session()->flash('status', __('Permission created successfully.'));
        }

        $this->showingModal = false;
        $this->form->reset();
    }

    public function confirmDelete(string $id): void
    {
        $permission = Permission::findOrFail($id);
        $this->authorize('delete', $permission);

        $this->deletingPermissionId = $permission->id;
        $this->deletingPermissionName = $permission->name;
        $this->showingDeleteModal = true;
    }

    public function deletePermission(DeletePermissionAction $deleteAction): void
    {
        if ($this->deletingPermissionId === null) {
            return;
        }

        $permission = Permission::findOrFail($this->deletingPermissionId);
        $this->authorize('delete', $permission);

        $deleteAction($permission);

        $this->showingDeleteModal = false;
        $this->deletingPermissionId = null;
        $this->deletingPermissionName = null;
        session()->flash('status', __('Permission deleted successfully.'));
    }

    public function restorePermission(string $id, RestorePermissionAction $restoreAction): void
    {
        $permission = Permission::withTrashed()->findOrFail($id);
        $this->authorize('restore', $permission);

        $restoreAction($permission);
        session()->flash('status', __('Permission restored successfully.'));
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'resourceFilter', 'showDeleted']);
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Permission::query()->withCount('roles');

        if ($this->showDeleted) {
            $query->onlyTrashed();
        }

        if (filled($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('slug', 'like', '%'.$this->search.'%');
            });
        }

        if (filled($this->resourceFilter)) {
            $query->where('slug', 'like', $this->resourceFilter.'.%');
        }

        $permissions = $query->orderBy('slug')->paginate(15);

        $resources = Permission::query()
            ->pluck('slug')
            ->map(function ($slug) {
                $parts = explode('.', $slug);

                return count($parts) > 1 ? $parts[0] : null;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $stats = [
            'total' => Permission::withTrashed()->count(),
            'active' => Permission::withoutTrashed()->count(),
            'resources_count' => $resources->count(),
            'trashed' => Permission::onlyTrashed()->count(),
        ];

        return view('domain.auth.permissions.index', [
            'permissions' => $permissions,
            'resources' => $resources,
            'stats' => $stats,
        ]);
    }
}
