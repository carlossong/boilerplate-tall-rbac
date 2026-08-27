<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Auth\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(3),
            'level' => 10,
            'is_system' => false,
            'description' => fake()->sentence(),
        ];

    }

    public function system(): static
    {
        return $this->state(fn () => [
            'is_system' => true,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'name' => 'Administrator',
            'slug' => 'admin',
            'level' => 80,
            'is_system' => true,
        ]);
    }

    public function manager(): static
    {
        return $this->state(fn () => [
            'name' => 'Manager',
            'slug' => 'manager',
            'level' => 50,
        ]);
    }

    public function operator(): static
    {
        return $this->state(fn () => [
            'name' => 'Operator',
            'slug' => 'operator',
            'level' => 20,
        ]);
    }
}
