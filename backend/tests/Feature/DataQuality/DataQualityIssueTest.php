<?php

use App\Enums\DataQualityIssueStatus;
use App\Enums\RoleEnum;
use App\Models\Beneficiary;
use App\Models\DataQualityIssue;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);

    $this->fieldOfficer = User::factory()->create();
    $this->fieldOfficer->assignRole(RoleEnum::FieldOfficer->value);

    $this->financeOfficer = User::factory()->create();
    $this->financeOfficer->assignRole(RoleEnum::FinanceOfficer->value);
});

function openIssueFor(Beneficiary $beneficiary): DataQualityIssue
{
    return DataQualityIssue::create([
        'entity_type' => Beneficiary::class,
        'entity_id' => $beneficiary->id,
        'issue_type' => 'duplicate_beneficiary',
        'severity' => 'warning',
        'description' => 'Possible duplicate.',
        'status' => DataQualityIssueStatus::Open,
        'detected_at' => now(),
    ]);
}

test('open issues can be listed and filtered by status', function () {
    $beneficiary = Beneficiary::factory()->create();
    $issue = openIssueFor($beneficiary);
    $issue->update(['status' => DataQualityIssueStatus::Resolved]);
    $openOne = openIssueFor(Beneficiary::factory()->create());

    $response = $this->actingAs($this->fieldOfficer)
        ->getJson('/api/v1/data-quality/issues?filter[status]=open')
        ->assertOk();

    expect(collect($response->json('data'))->pluck('id')->all())->toEqual([$openOne->id]);
});

test('a field officer can resolve an issue', function () {
    $issue = openIssueFor(Beneficiary::factory()->create());

    $this->actingAs($this->fieldOfficer)
        ->postJson("/api/v1/data-quality/issues/{$issue->id}/resolve")
        ->assertOk()
        ->assertJsonPath('data.status', 'resolved');

    expect($issue->fresh()->resolved_by)->toBe($this->fieldOfficer->id);
});

test('a field officer can ignore an issue', function () {
    $issue = openIssueFor(Beneficiary::factory()->create());

    $this->actingAs($this->fieldOfficer)
        ->postJson("/api/v1/data-quality/issues/{$issue->id}/ignore")
        ->assertOk()
        ->assertJsonPath('data.status', 'ignored');
});

test('a finance officer cannot manage data quality issues', function () {
    $issue = openIssueFor(Beneficiary::factory()->create());

    $this->actingAs($this->financeOfficer)
        ->postJson("/api/v1/data-quality/issues/{$issue->id}/resolve")
        ->assertForbidden();
});

test('the score reflects open issues against total beneficiaries', function () {
    Beneficiary::factory()->count(3)->create();
    $flagged = Beneficiary::factory()->create();
    openIssueFor($flagged);

    $response = $this->actingAs($this->fieldOfficer)
        ->getJson('/api/v1/data-quality/score')
        ->assertOk();

    expect($response->json('data.total_beneficiaries'))->toBe(4);
    expect($response->json('data.open_issues'))->toBe(1);
    expect((float) $response->json('data.score'))->toBe(75.0);
});
