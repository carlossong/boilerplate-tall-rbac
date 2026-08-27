<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Auth;

use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class GateDynamicTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_creation_invalidates_cached_slugs(): void
    {
        Cache::forever('auth.permissions.slugs', ['existing.permission']);

        Permission::create([
            'name' => 'New Permission',
            'slug' => 'new.permission',
        ]);

        $this->assertFalse(Cache::has('auth.permissions.slugs'));
    }

    public function test_dynamic_gate_authorizes_user_based_on_assigned_role(): void
    {
        $permission = Permission::create([
            'name' => 'Reports Export',
            'slug' => 'reports.export',
        ]);

        $role = Role::create([
            'name' => 'Analyst',
            'slug' => 'analyst',
        ]);
        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        // Registra o gate dinâmico como o provider faz
        Gate::define($permission->slug, fn ($u) => method_exists($u, 'hasPermissionTo') && $u->hasPermissionTo($permission->slug));

        $this->assertTrue($user->can('reports.export'));

        $unauthorizedUser = User::factory()->create();
        $this->assertFalse($unauthorizedUser->can('reports.export'));
    }
}
