<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityStatus;
use App\Enums\DataQualityIssueStatus;
use App\Enums\EmployeeStatus;
use App\Enums\ExpenseStatus;
use App\Enums\PermissionEnum;
use App\Enums\ProgramStatus;
use App\Enums\VolunteerStatus;
use App\Enums\WorkflowInstanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\DataQualityIssue;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Program;
use App\Models\Volunteer;
use App\Models\WorkflowInstance;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Executive dashboard KPIs + chart series (see docs/database-design.md §11).
 * Every section below is a live query against Phase 1-5 tables — nothing is
 * fabricated or hardcoded. A section is omitted entirely (not a fabricated
 * zero) when the caller lacks its permission, mirroring
 * frontend/src/components/layout/nav-items.ts's requiresAnyPermission
 * composition on the server side.
 */
class DashboardController extends Controller
{
    public function kpis(): JsonResponse
    {
        $data = [];

        if (Gate::allows(PermissionEnum::ViewAnyPrograms->value)) {
            $data['programs'] = $this->programsSection();
        }

        if (Gate::allows(PermissionEnum::ViewAnyBeneficiaries->value)) {
            $data['beneficiaries'] = $this->beneficiariesSection();
        }

        if (Gate::allows(PermissionEnum::ViewAnyEmployees->value) || Gate::allows(PermissionEnum::ViewAnyVolunteers->value)) {
            $data['staffing'] = $this->staffingSection();
        }

        if (Gate::allows(PermissionEnum::ViewAnyActivities->value)) {
            $data['activities'] = $this->activitiesSection();
        }

        if (Gate::allows(PermissionEnum::ViewAnyExpenses->value)) {
            $data['finance'] = $this->financeSection();
        }

        if (Gate::allows(PermissionEnum::ViewAnyDataQualityIssues->value)) {
            $data['data_quality'] = $this->dataQualitySection();
        }

        if (Gate::allows(PermissionEnum::ViewAnyWorkflows->value)) {
            $data['workflows'] = $this->workflowsSection();
        }

        return ApiResponse::data($data);
    }

    private function programsSection(): array
    {
        $counts = Program::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        $statusBreakdown = collect(ProgramStatus::cases())
            ->mapWithKeys(fn (ProgramStatus $status) => [$status->value => (int) ($counts[$status->value] ?? 0)]);

        return [
            'active_programs' => $statusBreakdown[ProgramStatus::Active->value],
            'total_programs' => (int) $statusBreakdown->sum(),
            'status_breakdown' => $statusBreakdown,
        ];
    }

    private function beneficiariesSection(): array
    {
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months[Carbon::now()->subMonthsNoOverflow($i)->format('Y-m')] = 0;
        }

        // Grouped in PHP, not SQL date functions — tests run on SQLite,
        // production on Postgres (see docs/database-design.md §9).
        $registrationDates = Beneficiary::query()
            ->where('registration_date', '>=', Carbon::now()->startOfMonth()->subMonthsNoOverflow(5))
            ->pluck('registration_date');

        foreach ($registrationDates as $date) {
            $key = Carbon::parse($date)->format('Y-m');
            if ($months->has($key)) {
                $months[$key] = $months[$key] + 1;
            }
        }

        return [
            'total_beneficiaries' => Beneficiary::count(),
            'enrolled_by_month' => $months,
        ];
    }

    private function staffingSection(): array
    {
        $section = [];

        if (Gate::allows(PermissionEnum::ViewAnyEmployees->value)) {
            $section['active_employees'] = Employee::where('status', EmployeeStatus::Active)->count();
        }

        if (Gate::allows(PermissionEnum::ViewAnyVolunteers->value)) {
            $section['active_volunteers'] = Volunteer::where('status', VolunteerStatus::Active)->count();
        }

        return $section;
    }

    private function activitiesSection(): array
    {
        return [
            'activities_this_month' => Activity::query()
                ->where('status', '!=', ActivityStatus::Cancelled)
                ->whereBetween('scheduled_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->count(),
        ];
    }

    private function financeSection(): array
    {
        $totalBudget = (float) Program::sum('budget');
        $approvedExpenses = (float) Expense::where('status', ExpenseStatus::Approved)->sum('amount');

        $byCategory = Expense::query()
            ->where('expenses.status', ExpenseStatus::Approved)
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->selectRaw("coalesce(expense_categories.name, 'Uncategorized') as category, sum(expenses.amount) as aggregate")
            ->groupBy('category')
            ->pluck('aggregate', 'category')
            ->map(fn ($value) => (float) $value);

        return [
            'total_budget' => $totalBudget,
            'approved_expenses' => $approvedExpenses,
            'budget_utilization_pct' => $totalBudget > 0 ? round(($approvedExpenses / $totalBudget) * 100, 1) : 0.0,
            'expenses_by_category' => $byCategory,
        ];
    }

    private function dataQualitySection(): array
    {
        $counts = DataQualityIssue::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        $byStatus = collect(DataQualityIssueStatus::cases())
            ->mapWithKeys(fn ($status) => [$status->value => (int) ($counts[$status->value] ?? 0)]);

        return [...DataQualityIssue::computeScore(), 'by_status' => $byStatus];
    }

    private function workflowsSection(): array
    {
        return [
            'pending_approvals' => WorkflowInstance::where('status', WorkflowInstanceStatus::InProgress)->count(),
        ];
    }
}
