<?php

namespace App\Providers;

use App\Domain\Auth\Models\Permission;
use App\Domain\Auth\Models\Role;
use App\Domain\Auth\Policies\PermissionPolicy;
use App\Domain\Auth\Policies\RolePolicy;
use App\Domain\Auth\Policies\UserPolicy;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
    }

    /**
     * Configure native authorization, policies and dynamic gates.
     */
    protected function configureAuthorization(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(\App\Domain\Auth\Models\User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);

        Gate::before(function ($user, string $ability) {
            if ($user instanceof \App\Domain\Auth\Models\User && $user->isSuperAdmin()) {
                return true;
            }

            return null;
        });

        Gate::after(function ($user, string $ability, ?bool $result) {
            if ($result !== null) {
                return $result;
            }

            if ($user instanceof \App\Domain\Auth\Models\User) {
                return $user->hasPermissionTo($ability);
            }

            return null;
        });

        try {
            if (Schema::hasTable('permissions')) {
                $permissionSlugs = Cache::rememberForever('auth.permissions.slugs', function () {
                    return Permission::query()->pluck('slug')->all();
                });

                foreach ($permissionSlugs as $slug) {
                    Gate::define($slug, function ($user) use ($slug) {
                        return $user instanceof \App\Domain\Auth\Models\User && $user->hasPermissionTo($slug);
                    });
                }
            }
        } catch (\Throwable) {
            // Evita quebra durante migrações iniciais antes da existência da tabela
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
