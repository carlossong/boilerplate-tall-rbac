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

    public int $level = 10;

    public string $description = '';

    /**
     * @var array<string>
     */
    public array $permission_ids = [];

    /**
     * @var array<string>
     */
    public array $department_ids = [];

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
            'level' => ['required', 'integer', 'min:1', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['exists:permissions,id'],
            'department_ids' => ['array'],
            'department_ids.*' => ['exists:departments,id'],
        ];
    }

    public function setRole(?Role $role): void
    {
        if ($role === null) {
            $this->reset();
            $this->level = 10;

            return;
        }

        $this->id = $role->id;
        $this->name = $role->name;
        $this->slug = $role->slug;
        $this->level = $role->level ?? 10;
        $this->description = $role->description ?? '';
        $this->permission_ids = $role->permissions()->pluck('permissions.id')->all();
        $this->department_ids = $role->departments()->pluck('departments.id')->all();
    }

    public function toDTO(): RoleData
    {
        return new RoleData(
            name: $this->name,
            slug: $this->slug,
            level: (int) $this->level,
            description: filled($this->description) ? $this->description : null,
            permissionIds: $this->permission_ids,
            departmentIds: $this->department_ids,
        );
    }
}
