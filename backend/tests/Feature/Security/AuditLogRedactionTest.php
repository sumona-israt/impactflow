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
});

test('creating a beneficiary redacts PII in the audit trail, not just password fields', function () {
    $this->actingAs($this->fieldOfficer)->postJson('/api/v1/beneficiaries', [
        'full_name' => 'Test Beneficiary',
        'phone' => '01711111111',
        'date_of_birth' => '1990-01-01',
        'address' => '123 Test Road, Dhaka',
        'emergency_contact_name' => 'Test Contact',
        'emergency_contact_phone' => '01722222222',
        'registration_date' => now()->toDateString(),
    ])->assertCreated();

    $log = AuditLog::where('action', 'beneficiary.created')->firstOrFail();

    expect($log->new_values['phone'])->toBe('[REDACTED]');
    expect($log->new_values['date_of_birth'])->toBe('[REDACTED]');
    expect($log->new_values['address'])->toBe('[REDACTED]');
    expect($log->new_values['emergency_contact_name'])->toBe('[REDACTED]');
    expect($log->new_values['emergency_contact_phone'])->toBe('[REDACTED]');
    expect($log->new_values['full_name'])->toBe('Test Beneficiary');
});

test('updating a beneficiary redacts PII in old and new values', function () {
    $beneficiary = Beneficiary::factory()->create(['phone' => '01711111111']);

    $this->actingAs($this->fieldOfficer)
        ->putJson("/api/v1/beneficiaries/{$beneficiary->id}", ['phone' => '01799999999'])
        ->assertOk();

    $log = AuditLog::where('action', 'beneficiary.updated')->firstOrFail();

    expect($log->old_values['phone'])->toBe('[REDACTED]');
    expect($log->new_values['phone'])->toBe('[REDACTED]');
});
