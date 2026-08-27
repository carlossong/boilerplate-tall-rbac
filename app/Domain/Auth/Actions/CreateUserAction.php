<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTOs\UserData;
use App\Domain\Auth\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final readonly class CreateUserAction
{
    public function __invoke(UserData $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password ?? Str::random(32)),
                'is_super_admin' => $data->isSuperAdmin,
            ]);

            if (! empty($data->roleIds)) {
                $user->roles()->sync($data->roleIds);
            }

            if (! empty($data->departmentAssignments)) {
                $syncData = [];
                foreach ($data->departmentAssignments as $item) {
                    if (is_string($item)) {
                        $syncData[$item] = [
                            'id' => (string) Str::uuid(),
                            'role_id' => null,
                            'is_primary' => empty($syncData),
                        ];
                    } elseif (is_array($item) && isset($item['department_id'])) {
                        $syncData[$item['department_id']] = [
                            'id' => (string) Str::uuid(),
                            'role_id' => ! empty($item['role_id']) ? $item['role_id'] : null,
                            'is_primary' => (bool) ($item['is_primary'] ?? false),
                        ];
                    }
                }

                $user->departments()->sync($syncData);
            }

            return $user;
        });
    }
}
