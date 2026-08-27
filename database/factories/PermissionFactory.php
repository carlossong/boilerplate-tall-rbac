<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Auth\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $word = fake()->unique()->word();
        $slug = 'custom.'.$word;

        return [
            'name' => Str::title($word).' Permission',
            'slug' => $slug,
            'group' => Permission::groupFromSlug($slug),
            'description' => fake()->sentence(),
        ];
    }
}
