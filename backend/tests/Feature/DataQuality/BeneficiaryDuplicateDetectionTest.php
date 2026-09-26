<?php

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
});

test('creating a beneficiary matching an existing one on name and phone opens a duplicate issue', function () {
    $existing = Beneficiary::factory()->create(['full_name' => 'Rahim Uddin', 'phone' => '01712345678']);

    $response = $this->actingAs($this->fieldOfficer)->postJson('/api/v1/beneficiaries', [
        'full_name' => '  rahim uddin  ',
        'phone' => '017-1234-5678',
        'registration_date' => now()->toDateString(),
    ])->assertCreated();

    $newId = $response->json('data.id');

    $issue = DataQualityIssue::query()
        ->where('entity_type', Beneficiary::class)
        ->where('entity_id', $newId)
        ->where('issue_type', 'duplicate_beneficiary')
        ->first();

    expect($issue)->not->toBeNull();
    expect($issue->status->value)->toBe('open');
    expect($issue->description)->toContain($existing->full_name);
});

test('creating a beneficiary with no matching name or phone opens no issue', function () {
    Beneficiary::factory()->create(['full_name' => 'Karim Ahmed', 'phone' => '01711111111']);

    $response = $this->actingAs($this->fieldOfficer)->postJson('/api/v1/beneficiaries', [
        'full_name' => 'Someone Else',
        'phone' => '01799999999',
        'registration_date' => now()->toDateString(),
    ])->assertCreated();

    expect(DataQualityIssue::where('entity_id', $response->json('data.id'))->exists())->toBeFalse();
});

test('updating a beneficiary phone to match another beneficiary opens an issue', function () {
    Beneficiary::factory()->create(['full_name' => 'Nasrin Akter', 'phone' => '01755555555']);
    $beneficiary = Beneficiary::factory()->create(['full_name' => 'Different Name', 'phone' => '01700000000']);

    $this->actingAs($this->fieldOfficer)
        ->putJson("/api/v1/beneficiaries/{$beneficiary->id}", ['full_name' => 'Nasrin Akter'])
        ->assertOk();

    // Name alone isn't enough — phone still differs, so no issue yet.
    expect(DataQualityIssue::where('entity_id', $beneficiary->id)->exists())->toBeFalse();

    $this->actingAs($this->fieldOfficer)
        ->putJson("/api/v1/beneficiaries/{$beneficiary->id}", ['phone' => '01755555555'])
        ->assertOk();

    expect(DataQualityIssue::where('entity_id', $beneficiary->id)->where('issue_type', 'duplicate_beneficiary')->count())->toBe(1);
});

test('repeated updates do not spam duplicate open issues for the same pair', function () {
    Beneficiary::factory()->create(['full_name' => 'Jamal Hossain', 'phone' => '01766666666']);
    $beneficiary = Beneficiary::factory()->create(['full_name' => 'Different Name', 'phone' => '01799999999']);

    $this->actingAs($this->fieldOfficer)
        ->putJson("/api/v1/beneficiaries/{$beneficiary->id}", ['full_name' => 'Jamal Hossain', 'phone' => '01766666666'])
        ->assertOk();

    expect(DataQualityIssue::where('entity_id', $beneficiary->id)->where('status', 'open')->count())->toBe(1);

    // A further edit that still resolves to the same duplicate pair
    // shouldn't open a second issue for it.
    $this->actingAs($this->fieldOfficer)
        ->putJson("/api/v1/beneficiaries/{$beneficiary->id}", ['full_name' => 'jamal hossain'])
        ->assertOk();

    expect(DataQualityIssue::where('entity_id', $beneficiary->id)->where('status', 'open')->count())->toBe(1);
});
