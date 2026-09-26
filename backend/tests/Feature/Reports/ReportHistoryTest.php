<?php

use App\Enums\ReportFormat;
use App\Enums\ReportType;
use App\Enums\RoleEnum;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    Storage::fake('local');

    $this->financeOfficer = User::factory()->create();
    $this->financeOfficer->assignRole(RoleEnum::FinanceOfficer->value);

    $this->management = User::factory()->create();
    $this->management->assignRole(RoleEnum::Management->value);
});

function reportFor(ReportType $type, ReportFormat $format, User $generator): Report
{
    Storage::disk('local')->put('reports/fixture.'.$format->value, 'fixture content');

    return Report::create([
        'type' => $type,
        'format' => $format,
        'parameters' => [],
        'file_path' => 'reports/fixture.'.$format->value,
        'generated_by' => $generator->id,
        'generated_at' => now(),
    ]);
}

test('report history only lists types the caller is permitted to generate', function () {
    reportFor(ReportType::Financial, ReportFormat::Csv, $this->management);
    reportFor(ReportType::Beneficiaries, ReportFormat::Csv, $this->management);

    $response = $this->actingAs($this->financeOfficer)
        ->getJson('/api/v1/reports')
        ->assertOk();

    $types = collect($response->json('data'))->pluck('type')->all();

    expect($types)->toContain(ReportType::Financial->value)
        ->and($types)->not->toContain(ReportType::Beneficiaries->value);
});

test('re-downloading a report is forbidden for a role lacking that report type permission, even though the row exists', function () {
    $report = reportFor(ReportType::Beneficiaries, ReportFormat::Csv, $this->management);

    $this->actingAs($this->financeOfficer)
        ->get("/api/v1/reports/{$report->id}/download")
        ->assertForbidden();

    $this->actingAs($this->management)
        ->get("/api/v1/reports/{$report->id}/download")
        ->assertOk();
});
