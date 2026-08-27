<?php

declare(strict_types=1);

namespace App\Domain\Auth\Livewire\Roles;

use App\Domain\Auth\Actions\ToggleRolePermissionAction;
use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use DomainException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Permission Matrix')]
class RolePermissionMatrix extends Component
{
    public string $search = '';

    public string $groupFilter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Role::class);
    }

    public function toggle(string $roleId, string $permissionId, ToggleRolePermissionAction $toggleAction): void
    {
        $role = Role::query()->findOrFail($roleId);
        $this->authorize('update', $role);

        try {
            $toggleAction($role, Permission::query()->findOrFail($permissionId));
        } catch (DomainException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'groupFilter']);
    }

    public function render(): View
    {
        $roles = Role::query()
            ->with('permissions')
            ->withCount('permissions')
            ->orderByLevel()
            ->get();

        $permissionsQuery = Permission::query()->orderBy('group')->orderBy('slug');

        if (filled($this->groupFilter)) {
            $permissionsQuery->where('group', $this->groupFilter);
        }

        if (filled($this->search)) {
            $term = '%'.$this->search.'%';
            $permissionsQuery->where(function ($query) use ($term): void {
                $query->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term);
            });
        }

        $permissions = $permissionsQuery->get();
        $groupedPermissions = $permissions->groupBy(fn (Permission $permission): string => $permission->groupKey());

        $granted = [];
        foreach ($roles as $role) {
            foreach ($role->permissions as $permission) {
                $granted[$role->id][$permission->id] = true;
            }
        }

        $actor = auth()->user();
        $editable = [];
        foreach ($roles as $role) {
            $editable[$role->id] = $actor instanceof User
                && ! $role->isSystem()
                && $actor->can('update', $role);
        }

        $groups = Permission::query()
            ->where('group', '!=', '')
            ->distinct()
            ->orderBy('group')
            ->pluck('group');

        return view('domain.auth.roles.matrix', [
            'roles' => $roles,
            'groupedPermissions' => $groupedPermissions,
            'granted' => $granted,
            'editable' => $editable,
            'groups' => $groups,
        ]);
    }
}
