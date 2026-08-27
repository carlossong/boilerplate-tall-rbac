<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTOs\UserData;
use App\Domain\Auth\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final readonly class UpdateUserAction
{
    public function __invoke(User $user, UserData $data): User
    {
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
            $user->flushCachedPermissions();

            return $user;
        });
    }
}
