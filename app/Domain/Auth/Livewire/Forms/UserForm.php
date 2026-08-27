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
    }

    public function toDTO(): UserData
    {
        return new UserData(
            name: $this->name,
            email: $this->email,
            password: filled($this->password) ? $this->password : null,
            isSuperAdmin: $this->is_super_admin,
            roleIds: $this->role_ids,
        );
    }
}
