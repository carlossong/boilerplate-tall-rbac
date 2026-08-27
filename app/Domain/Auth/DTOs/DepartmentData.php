<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTOs;

final readonly class DepartmentData
{
    /**
     * @param  array<string>  $roleIds
     */
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description = null,
        public bool $isActive = true,
        public array $roleIds = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            slug: (string) ($data['slug'] ?? ''),
            description: isset($data['description']) && filled($data['description']) ? (string) $data['description'] : null,
            isActive: (bool) ($data['is_active'] ?? true),
            roleIds: (array) ($data['role_ids'] ?? []),
        );
    }
}
