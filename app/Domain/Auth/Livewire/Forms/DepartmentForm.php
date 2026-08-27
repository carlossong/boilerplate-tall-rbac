<?php

declare(strict_types=1);

namespace App\Domain\Auth\Livewire\Forms;

use App\Domain\Auth\DTOs\DepartmentData;
use App\Domain\Auth\Models\Department;
use Illuminate\Validation\Rule;
use Livewire\Form;

class DepartmentForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public bool $is_active = true;

    /**
     * @var array<string>
     */
    public array $role_ids = [];

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
                Rule::unique('departments', 'slug')->ignore($this->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'role_ids' => ['array'],
            'role_ids.*' => ['exists:roles,id'],
        ];
    }

    public function setDepartment(?Department $department): void
    {
        if ($department === null) {
            $this->reset();
            $this->is_active = true;

            return;
        }

        $this->id = $department->id;
        $this->name = $department->name;
        $this->slug = $department->slug;
        $this->description = $department->description ?? '';
        $this->is_active = (bool) $department->is_active;
        $this->role_ids = $department->roles()->pluck('roles.id')->all();
    }

    public function toDTO(): DepartmentData
    {
        return new DepartmentData(
            name: $this->name,
            slug: $this->slug,
            description: filled($this->description) ? $this->description : null,
            isActive: $this->is_active,
            roleIds: $this->role_ids,
        );
    }
}
