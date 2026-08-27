<?php

declare(strict_types=1);

namespace App\Domain\Auth\Livewire\Forms;

use App\Domain\Auth\DTOs\PermissionData;
use App\Domain\Auth\Models\Permission;
use Illuminate\Validation\Rule;
use Livewire\Form;

class PermissionForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

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
                'regex:/^[a-z0-9]+(\.[a-z0-9_\-]+)+$/',
                Rule::unique('permissions', 'slug')->ignore($this->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => __('The permission slug must follow the pattern "resource.action" (e.g. users.view, roles.create).'),
        ];
    }

    public function setPermission(?Permission $permission): void
    {
        if ($permission === null) {
            $this->reset();

            return;
        }

        $this->id = $permission->id;
        $this->name = $permission->name;
        $this->slug = $permission->slug;
        $this->description = $permission->description ?? '';
    }

    public function toDTO(): PermissionData
    {
        return new PermissionData(
            name: $this->name,
            slug: $this->slug,
            description: filled($this->description) ? $this->description : null,
        );
    }
}
