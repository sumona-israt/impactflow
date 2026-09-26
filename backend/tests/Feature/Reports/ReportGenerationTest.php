<?php

use App\Enums\ExpenseStatus;
use App\Enums\ReportFormat;
use App\Enums\ReportType;
use App\Enums\RoleEnum;
use App\Models\Beneficiary;
use App\Models\DataQualityIssue;
use App\Models\Expense;
use App\Models\Program;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    Storage::fake('local');

    $this->programManager = User::factory()->create();
    $this->programManager->assignRole(RoleEnum::ProgramManager->value);

    $this->financeOfficer = User::factory()->create();
    $this->financeOfficer->assignRole(RoleEnum::FinanceOfficer->value);

    $this->hrAdminOfficer = User::factory()->create();
    $this->hrAdminOfficer->assignRole(RoleEnum::HrAdminOfficer->value);
});

test('a program manager can generate all four report types', function (ReportType $type) {
    Program::factory()->count(2)->create();
    Beneficiary::factory()->count(2)->create();
    Expense::factory()->create(['status' => ExpenseStatus::Approved]);
    DataQualityIssue::create([
        'entity_type' => Beneficiary::class,
        'entity_id' => Beneficiary::factory()->create()->id,
        'issue_type' => 'duplicate_beneficiary',
        'severity' => 'warning',
        'description' => 'Possible duplicate.',
        'status' => 'open',
        'detected_at' => now(),
    ]);

    $this->actingAs($this->programManager)
        ->get("/api/v1/reports/{$type->value}?format=csv")
        ->assertOk();

    expect(Report::query()->where('type', $type)->count())->toBe(1);
})->with([
    ReportType::ProgramPerformance,
    ReportType::Beneficiaries,
    ReportType::Financial,
    ReportType::DataQuality,
]);

test('each format produces a downloadable file with the right content type', function (ReportFormat $format) {
    Program::factory()->count(2)->create();

    $response = $this->actingAs($this->programManager)
        ->get('/api/v1/reports/'.ReportType::ProgramPerformance->value.'?format='.$format->value)
        ->assertOk();

    expect($response->headers->get('content-type'))->toContain($format->mimeType());

    $report = Report::first();
    expect($report->format)->toBe($format);
    Storage::disk('local')->assertExists($report->file_path);
})->with([ReportFormat::Csv, ReportFormat::Xlsx, ReportFormat::Pdf]);

test('a finance officer can generate financial and program-performance reports but not beneficiaries or data-quality', function () {
    $this->actingAs($this->financeOfficer)
        ->get('/api/v1/reports/'.ReportType::Financial->value.'?format=csv')
        ->assertOk();

    $this->actingAs($this->financeOfficer)
        ->get('/api/v1/reports/'.ReportType::ProgramPerformance->value.'?format=csv')
        ->assertOk();

    $this->actingAs($this->financeOfficer)
        ->get('/api/v1/reports/'.ReportType::Beneficiaries->value.'?format=csv')
        ->assertForbidden();

    $this->actingAs($this->financeOfficer)
        ->get('/api/v1/reports/'.ReportType::DataQuality->value.'?format=csv')
        ->assertForbidden();
});

test('an hr admin officer cannot generate any report type', function (ReportType $type) {
    $this->actingAs($this->hrAdminOfficer)
        ->get("/api/v1/reports/{$type->value}?format=csv")
        ->assertForbidden();
})->with([
    ReportType::ProgramPerformance,
    ReportType::Beneficiaries,
    ReportType::Financial,
    ReportType::DataQuality,
]);

test('the financial report only includes approved expenses by default', function () {
    $program = Program::factory()->create(['name' => 'Clean Water Access']);
    Expense::factory()->for($program)->create(['status' => ExpenseStatus::Approved, 'amount' => 111.50]);
    Expense::factory()->for($program)->create(['status' => ExpenseStatus::Draft, 'amount' => 222.75]);

    $this->actingAs($this->financeOfficer)
        ->get('/api/v1/reports/'.ReportType::Financial->value.'?format=csv')
        ->assertOk();

    $csv = Storage::disk('local')->get(Report::first()->file_path);

    expect($csv)->toContain('111.50')
        ->and($csv)->not->toContain('222.75');
});

test('an unknown report type 404s rather than being silently accepted', function () {
    $this->actingAs($this->programManager)
        ->get('/api/v1/reports/not-a-real-type?format=csv')
        ->assertNotFound();
});
