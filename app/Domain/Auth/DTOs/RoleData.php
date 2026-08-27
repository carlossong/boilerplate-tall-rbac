<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTOs;

final readonly class RoleData
{
    /**
     * @param  array<string>  $permissionIds
     */
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description = null,
        public array $permissionIds = [],
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
            permissionIds: (array) ($data['permission_ids'] ?? []),
        );
    }
}
