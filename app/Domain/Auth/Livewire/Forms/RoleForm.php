<?php

declare(strict_types=1);

namespace App\Domain\Auth\Livewire\Forms;

use App\Domain\Auth\DTOs\RoleData;
use App\Domain\Auth\Models\Role;
use Illuminate\Validation\Rule;
use Livewire\Form;

class RoleForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    /**
     * @var array<string>
     */
    public array $permission_ids = [];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'slug')->ignore($this->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['exists:permissions,id'],
        ];
    }

    public function setRole(?Role $role): void
    {
        if ($role === null) {
            $this->reset();

            return;
        }

        $this->id = $role->id;
        $this->name = $role->name;
        $this->slug = $role->slug;
        $this->description = $role->description ?? '';
        $this->permission_ids = $role->permissions()->pluck('permissions.id')->all();
    }

    public function toDTO(): RoleData
    {
        return new RoleData(
            name: $this->name,
            slug: $this->slug,
            description: filled($this->description) ? $this->description : null,
            permissionIds: $this->permission_ids,
        );
    }
}
