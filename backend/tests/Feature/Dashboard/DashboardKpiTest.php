<?php

use App\Enums\EmployeeStatus;
use App\Enums\ExpenseStatus;
use App\Enums\ProgramStatus;
use App\Enums\RoleEnum;
use App\Models\Beneficiary;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);

    $this->management = User::factory()->create();
    $this->management->assignRole(RoleEnum::Management->value);
});

afterEach(function () {
    Carbon::setTestNow();
});

test('dashboard KPIs reflect exact seeded counts, not fabricated placeholders', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-26'));

    Program::factory()->create(['status' => ProgramStatus::Active, 'budget' => 1000]);
    Program::factory()->create(['status' => ProgramStatus::Active, 'budget' => 1000]);
    $completedProgram = Program::factory()->create(['status' => ProgramStatus::Completed, 'budget' => 500]);

    Beneficiary::factory()->create(['registration_date' => Carbon::now()]);
    Beneficiary::factory()->create(['registration_date' => Carbon::now()]);
    Beneficiary::factory()->create(['registration_date' => Carbon::now()->subMonthsNoOverflow(2)]);

    Employee::factory()->create(['status' => EmployeeStatus::Active]);
    Employee::factory()->create(['status' => EmployeeStatus::Active]);
    Employee::factory()->create(['status' => EmployeeStatus::Terminated]);

    Expense::factory()->for($completedProgram)->create(['status' => ExpenseStatus::Approved, 'amount' => 300]);
    Expense::factory()->for($completedProgram)->create(['status' => ExpenseStatus::Draft, 'amount' => 9999]);

    $data = $this->actingAs($this->management)
        ->getJson('/api/v1/dashboard/kpis')
        ->assertOk()
        ->json('data');

    expect($data['programs']['active_programs'])->toBe(2)
        ->and($data['programs']['total_programs'])->toBe(3)
        ->and($data['beneficiaries']['total_beneficiaries'])->toBe(3)
        ->and($data['beneficiaries']['enrolled_by_month']['2026-09'])->toBe(2)
        ->and($data['beneficiaries']['enrolled_by_month']['2026-07'])->toBe(1)
        ->and($data['staffing']['active_employees'])->toBe(2)
        ->and((float) $data['finance']['total_budget'])->toBe(2500.0)
        ->and((float) $data['finance']['approved_expenses'])->toBe(300.0)
        ->and((float) $data['finance']['budget_utilization_pct'])->toBe(12.0);
});

test('a section is entirely absent from the response when the caller lacks its permission, never a fabricated zero', function () {
    $hrAdmin = User::factory()->create();
    $hrAdmin->assignRole(RoleEnum::HrAdminOfficer->value);

    Employee::factory()->create(['status' => EmployeeStatus::Active]);

    $data = $this->actingAs($hrAdmin)
        ->getJson('/api/v1/dashboard/kpis')
        ->assertOk()
        ->json('data');

    expect($data)->toHaveKey('staffing')
        ->and($data)->not->toHaveKey('programs')
        ->and($data)->not->toHaveKey('beneficiaries')
        ->and($data)->not->toHaveKey('activities')
        ->and($data)->not->toHaveKey('finance')
        ->and($data)->not->toHaveKey('data_quality')
        ->and($data)->not->toHaveKey('workflows');
});

test('management sees every dashboard section after the phase 6 default-permission update', function () {
    $data = $this->actingAs($this->management)
        ->getJson('/api/v1/dashboard/kpis')
        ->assertOk()
        ->json('data');

    expect($data)->toHaveKey('programs')
        ->and($data)->toHaveKey('beneficiaries')
        ->and($data)->toHaveKey('staffing')
        ->and($data)->toHaveKey('activities')
        ->and($data)->toHaveKey('finance')
        ->and($data)->toHaveKey('data_quality')
        ->and($data)->toHaveKey('workflows');
});

test('a finance officer sees finance but not beneficiaries', function () {
    $financeOfficer = User::factory()->create();
    $financeOfficer->assignRole(RoleEnum::FinanceOfficer->value);

    $data = $this->actingAs($financeOfficer)
        ->getJson('/api/v1/dashboard/kpis')
        ->assertOk()
        ->json('data');

    expect($data)->toHaveKey('finance')
        ->and($data)->not->toHaveKey('beneficiaries');
});
