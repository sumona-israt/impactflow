<?php

namespace App\Providers;

use App\Enums\RoleEnum;
use App\Models\AuditLog;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

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
        // Super Administrator: Everything (per the product brief) — bypasses
        // every policy/permission check below rather than needing every
        // permission explicitly assigned to it.
        Gate::before(fn (User $user) => $user->hasRole(RoleEnum::SuperAdmin->value) ? true : null);

        // UserPolicy is auto-discovered (App\Models\User <-> App\Policies\UserPolicy).
        // Role and AuditLog aren't app-namespaced models, so they need explicit registration.
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
    }
}
