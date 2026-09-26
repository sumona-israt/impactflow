<?php

namespace App\Providers;

use App\Contracts\OdooClientInterface;
use App\Enums\RoleEnum;
use App\Events\BeneficiaryRegistered;
use App\Events\EmployeeCreated;
use App\Events\ExpenseApproved;
use App\Events\ProgramApproved;
use App\Events\ProgramSubmittedForApproval;
use App\Events\WorkflowInstanceActed;
use App\Events\WorkflowInstanceStarted;
use App\Listeners\DispatchBeneficiaryRegisteredToOdoo;
use App\Listeners\DispatchEmployeeCreatedToOdoo;
use App\Listeners\DispatchExpenseApprovedToOdoo;
use App\Listeners\DispatchProgramApprovedToOdoo;
use App\Listeners\NotifyManagementOfProgramSubmission;
use App\Listeners\NotifyWorkflowParticipants;
use App\Models\AuditLog;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\RolePolicy;
use App\Services\Odoo\FakeOdooClient;
use App\Services\Odoo\OdooJsonRpcClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ODOO_MODE picks the transport — see docs/odoo-integration.md §5.
        $this->app->bind(OdooClientInterface::class, fn () => config('odoo.mode') === 'live'
            ? $this->app->make(OdooJsonRpcClient::class)
            : $this->app->make(FakeOdooClient::class));
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

        // Odoo integration (see docs/odoo-integration.md) — no EventServiceProvider
        // in this app, so registered explicitly here like the policies above.
        Event::listen(ProgramApproved::class, DispatchProgramApprovedToOdoo::class);
        Event::listen(ExpenseApproved::class, DispatchExpenseApprovedToOdoo::class);
        Event::listen(BeneficiaryRegistered::class, DispatchBeneficiaryRegisteredToOdoo::class);
        Event::listen(EmployeeCreated::class, DispatchEmployeeCreatedToOdoo::class);

        // Notifications (see docs/database-design.md §11) — same explicit
        // registration style as the Odoo listeners above.
        Event::listen(WorkflowInstanceStarted::class, [NotifyWorkflowParticipants::class, 'handleStarted']);
        Event::listen(WorkflowInstanceActed::class, [NotifyWorkflowParticipants::class, 'handleActed']);
        Event::listen(ProgramSubmittedForApproval::class, NotifyManagementOfProgramSubmission::class);

        // Rate limiting (see docs/implementation-plan.md Phase 9) — no
        // RouteServiceProvider exists in Laravel 11+ to define these in,
        // so registered here like everything else above.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->id ?: $request->ip()));
    }
}
