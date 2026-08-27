<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTOs;

final readonly class PermissionData
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description = null,
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
        );
    }
}
