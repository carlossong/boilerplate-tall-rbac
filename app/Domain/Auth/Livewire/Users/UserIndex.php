<?php

declare(strict_types=1);

namespace App\Domain\Auth\Livewire\Users;

use App\Domain\Auth\Actions\CreateUserAction;
use App\Domain\Auth\Actions\DeleteUserAction;
use App\Domain\Auth\Actions\RestoreUserAction;
use App\Domain\Auth\Actions\UpdateUserAction;
use App\Domain\Auth\Livewire\Forms\UserForm;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Users')]
class UserIndex extends Component
{
    use WithPagination;

    public UserForm $form;

    public string $search = '';

    public string $roleFilter = '';

    public bool $showDeleted = false;

    public bool $showingModal = false;

    public bool $showingDeleteModal = false;

    public ?string $deletingUserId = null;

    public ?string $deletingUserName = null;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatedShowDeleted(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', User::class);
        $this->form->reset();
        $this->resetValidation();
        $this->showingModal = true;
    }

    public function openEditModal(string $id): void
    {
        $user = User::withTrashed()->with('roles')->findOrFail($id);
        $this->authorize('update', $user);

        $this->form->setUser($user);
        $this->resetValidation();
        $this->showingModal = true;
    }

    public function save(CreateUserAction $createAction, UpdateUserAction $updateAction): void
    {
        $this->form->validate();

        if ($this->form->id !== null) {
            $user = User::withTrashed()->findOrFail($this->form->id);
            $this->authorize('update', $user);

            $updateAction($user, $this->form->toDTO());
            session()->flash('status', __('User updated successfully.'));
        } else {
            $this->authorize('create', User::class);

            $createAction($this->form->toDTO());
            session()->flash('status', __('User created successfully.'));
        }

        $this->showingModal = false;
        $this->form->reset();
    }

    public function confirmDelete(string $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);

        $this->deletingUserId = $user->id;
        $this->deletingUserName = $user->name;
        $this->showingDeleteModal = true;
    }

    public function deleteUser(DeleteUserAction $deleteAction): void
    {
        if ($this->deletingUserId === null) {
            return;
        }

        $user = User::findOrFail($this->deletingUserId);
        $this->authorize('delete', $user);

        $currentUser = auth()->user();
        $domainUser = $currentUser instanceof User ? $currentUser : null;

        $deleteAction($user, $domainUser);

        $this->showingDeleteModal = false;
        $this->deletingUserId = null;
        $this->deletingUserName = null;
        session()->flash('status', __('User deleted successfully.'));
    }

    public function restoreUser(string $id, RestoreUserAction $restoreAction): void
    {
        $user = User::withTrashed()->findOrFail($id);
        $this->authorize('restore', $user);

        $restoreAction($user);
        session()->flash('status', __('User restored successfully.'));
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'roleFilter', 'showDeleted']);
        $this->resetPage();
    }

    public function render(): View
    {
        $query = User::query()->with('roles');

        if ($this->showDeleted) {
            $query->onlyTrashed();
        }

        if (filled($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        if (filled($this->roleFilter)) {
            $query->whereHas('roles', fn ($q) => $q->where('roles.id', $this->roleFilter));
        }

        $users = $query->latest()->paginate(10);
        $allRoles = Role::orderBy('name')->get();

        $stats = [
            'total' => User::withTrashed()->count(),
            'active' => User::withoutTrashed()->count(),
            'super_admins' => User::where('is_super_admin', true)->withoutTrashed()->count(),
            'trashed' => User::onlyTrashed()->count(),
        ];

        return view('domain.auth.users.index', [
            'users' => $users,
            'allRoles' => $allRoles,
            'stats' => $stats,
        ]);
    }
}
