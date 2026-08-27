<?php

declare(strict_types=1);

namespace App\Domain\Auth\Livewire\Forms;

use App\Domain\Auth\DTOs\UserData;
use App\Domain\Auth\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Form;

class UserForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public bool $is_super_admin = false;

    /**
     * @var array<string>
     */
    public array $role_ids = [];

    /**
     * @var array<string>
     */
    public array $department_ids = [];

    /**
     * @var array<string, string|null>
     */
    public array $department_roles = [];

    public ?string $primary_department_id = null;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->id),
            ],
            'password' => [
                $this->id === null ? 'required' : 'nullable',
                'string',
                'min:8',
            ],
            'is_super_admin' => ['boolean'],
            'role_ids' => ['array'],
            'role_ids.*' => ['exists:roles,id'],
            'department_ids' => ['array'],
            'department_ids.*' => ['exists:departments,id'],
            'department_roles' => ['array'],
            'primary_department_id' => ['nullable', 'exists:departments,id'],
        ];
    }

    public function setUser(?User $user): void
    {
        if ($user === null) {
            $this->reset();

            return;
        }

        $this->id = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->is_super_admin = (bool) $user->is_super_admin;
        $this->role_ids = $user->roles()->pluck('roles.id')->all();

        $user->loadMissing('departments');
        $this->department_ids = $user->departments->pluck('id')->all();
        $this->department_roles = [];
        $this->primary_department_id = null;

        foreach ($user->departments as $dept) {
            $pivot = $dept->pivot;
            if ($pivot && ! empty($pivot->role_id)) {
                $this->department_roles[$dept->id] = (string) $pivot->role_id;
            }
            if ($pivot && ! empty($pivot->is_primary)) {
                $this->primary_department_id = $dept->id;
            }
        }

        if (empty($this->primary_department_id) && ! empty($this->department_ids)) {
            $this->primary_department_id = $this->department_ids[0];
        }
    }

    public function toDTO(): UserData
    {
        $departmentAssignments = [];
        foreach ($this->department_ids as $deptId) {
            $departmentAssignments[] = [
                'department_id' => $deptId,
                'role_id' => ! empty($this->department_roles[$deptId]) ? $this->department_roles[$deptId] : null,
                'is_primary' => $this->primary_department_id === $deptId,
            ];
        }

        return new UserData(
            name: $this->name,
            email: $this->email,
            password: filled($this->password) ? $this->password : null,
            isSuperAdmin: $this->is_super_admin,
            roleIds: $this->role_ids,
            departmentAssignments: $departmentAssignments,
        );
    }
}
