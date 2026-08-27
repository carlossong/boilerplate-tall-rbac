<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTOs\UserData;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final readonly class UpdateUserAction
{
    public function __invoke(User $user, UserData $data, ?User $actor = null): User
    {
        // 1. Lockout protection: ensure at least one active super admin remains
        if ($user->is_super_admin && ! $data->isSuperAdmin) {
            $otherActiveSuperAdmins = User::withoutTrashed()
                ->where('is_super_admin', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherActiveSuperAdmins === 0) {
                throw new DomainException(__('The system must have at least one active super administrator.'));
            }
        }

        // 2. Self-demotion / panel lockout protection
        if ($actor !== null && $actor->id === $user->id && $user->hasAdministrativeAccess()) {
            $newRoles = Role::whereIn('id', $data->roleIds)->get();
            $hasAdminRole = $newRoles->contains(fn (Role $role) => $role->isAdministrative());

            if (! $data->isSuperAdmin && ! $hasAdminRole) {
                throw new DomainException(__('You cannot remove your own administrative access or demote yourself from the management panel.'));
            }
        }

        return DB::transaction(function () use ($user, $data) {
            $attributes = [
                'name' => $data->name,
                'email' => $data->email,
                'is_super_admin' => $data->isSuperAdmin,
            ];

            if ($data->password !== null && filled($data->password)) {
                $attributes['password'] = Hash::make($data->password);
            }

            $user->update($attributes);
            $user->roles()->sync($data->roleIds);

            $user->syncDepartments($data->departmentAssignments);
            $user->flushCachedPermissions();

            return $user;
        });
    }
}
