<?php

use App\Enums\RoleEnum;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);

    $this->fieldOfficer = User::factory()->create();
    $this->fieldOfficer->assignRole(RoleEnum::FieldOfficer->value);

    $this->program = Program::factory()->create();
});

test('a field officer can create an activity under a program', function () {
    $response = $this->actingAs($this->fieldOfficer)->postJson("/api/v1/programs/{$this->program->id}/activities", [
        'title' => 'Community Health Session',
        'scheduled_at' => now()->addWeek()->toIso8601String(),
        'location' => 'Village Hall',
    ]);

    $response->assertCreated()->assertJsonPath('data.title', 'Community Health Session');
    expect(AuditLog::where('action', 'activity.created')->exists())->toBeTrue();
});

test('a field officer can record attendance for enrolled beneficiaries', function () {
    $activity = Activity::factory()->create(['program_id' => $this->program->id]);
    $beneficiary = Beneficiary::factory()->create();

    $response = $this->actingAs($this->fieldOfficer)->postJson("/api/v1/activities/{$activity->id}/attendance", [
        'attendance' => [$beneficiary->id => true],
    ]);

    $response->assertOk()->assertJsonPath('data.0.attended', true);

    $this->actingAs($this->fieldOfficer)
        ->getJson("/api/v1/activities/{$activity->id}/attendance")
        ->assertOk()
        ->assertJsonPath('data.0.beneficiary_name', $beneficiary->full_name);

    expect(AuditLog::where('action', 'activity.attendance_recorded')->exists())->toBeTrue();
});

test('a guest cannot access activity endpoints', function () {
    $activity = Activity::factory()->create(['program_id' => $this->program->id]);

    $this->getJson("/api/v1/activities/{$activity->id}")->assertUnauthorized();
});
