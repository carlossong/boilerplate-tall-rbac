<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTOs;

final readonly class UserData
{
    /**
     * @param  array<string>  $roleIds
     * @param  array<int|string, mixed>  $departmentAssignments
     * @param  array<string>  $permissionIds
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null,
        public bool $isSuperAdmin = false,
        public array $roleIds = [],
        public array $departmentAssignments = [],
        public array $permissionIds = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            password: isset($data['password']) && filled($data['password']) ? (string) $data['password'] : null,
            isSuperAdmin: (bool) ($data['is_super_admin'] ?? false),
            roleIds: (array) ($data['role_ids'] ?? []),
            departmentAssignments: (array) ($data['departments'] ?? $data['department_assignments'] ?? []),
            permissionIds: (array) ($data['permission_ids'] ?? []),
        );
    }
}
