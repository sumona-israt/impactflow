<?php

use App\Enums\RoleEnum;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);

    $this->fieldOfficer = User::factory()->create();
    $this->fieldOfficer->assignRole(RoleEnum::FieldOfficer->value);

    $this->hrOfficer = User::factory()->create();
    $this->hrOfficer->assignRole(RoleEnum::HrAdminOfficer->value);
});

test('a field officer can register a beneficiary and it is audited', function () {
    $response = $this->actingAs($this->fieldOfficer)->postJson('/api/v1/beneficiaries', [
        'full_name' => 'Rahim Ahmed',
        'registration_date' => '2026-01-15',
        'phone' => '01700000000',
        'district' => 'Dhaka',
    ]);

    $response->assertCreated()->assertJsonPath('data.full_name', 'Rahim Ahmed');
    expect(AuditLog::where('action', 'beneficiary.created')->exists())->toBeTrue();
});

test('an hr officer cannot register a beneficiary (not in their remit)', function () {
    $this->actingAs($this->hrOfficer)
        ->postJson('/api/v1/beneficiaries', ['full_name' => 'Should Fail', 'registration_date' => '2026-01-01'])
        ->assertForbidden();
});

test('the beneficiary list endpoint never exposes sensitive fields', function () {
    Beneficiary::factory()->create(['phone' => '01711111111', 'email' => 'secret@example.com']);

    $response = $this->actingAs($this->fieldOfficer)->getJson('/api/v1/beneficiaries')->assertOk();

    $response->assertJsonMissingPath('data.0.phone')
        ->assertJsonMissingPath('data.0.email')
        ->assertJsonMissingPath('data.0.date_of_birth');
});

test('the beneficiary detail endpoint returns full sensitive data to an authorized user', function () {
    $beneficiary = Beneficiary::factory()->create(['phone' => '01711111111']);

    $this->actingAs($this->fieldOfficer)
        ->getJson("/api/v1/beneficiaries/{$beneficiary->id}")
        ->assertOk()
        ->assertJsonPath('data.phone', '01711111111');
});
