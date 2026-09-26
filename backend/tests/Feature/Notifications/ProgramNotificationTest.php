<?php

use App\Enums\ProgramStatus;
use App\Enums\RoleEnum;
use App\Models\Program;
use App\Models\User;
use App\Notifications\ProgramAwaitingApproval;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);

    $this->programManager = User::factory()->create();
    $this->programManager->assignRole(RoleEnum::ProgramManager->value);

    $this->management = User::factory()->create();
    $this->management->assignRole(RoleEnum::Management->value);
});

test('moving a program to pending approval notifies every management user', function () {
    $anotherManager = User::factory()->create();
    $anotherManager->assignRole(RoleEnum::Management->value);

    Notification::fake();

    $program = Program::factory()->create(['status' => ProgramStatus::Draft]);

    $this->actingAs($this->programManager)
        ->patchJson("/api/v1/programs/{$program->id}/status", ['status' => ProgramStatus::PendingApproval->value])
        ->assertOk();

    Notification::assertSentTo($this->management, ProgramAwaitingApproval::class);
    Notification::assertSentTo($anotherManager, ProgramAwaitingApproval::class);
    Notification::assertNotSentTo($this->programManager, ProgramAwaitingApproval::class);
});

test('a later transition to approved does not re-notify management via ProgramAwaitingApproval', function () {
    $program = Program::factory()->create(['status' => ProgramStatus::Draft]);

    $this->actingAs($this->programManager)
        ->patchJson("/api/v1/programs/{$program->id}/status", ['status' => ProgramStatus::PendingApproval->value])
        ->assertOk();

    Notification::fake();

    $this->actingAs($this->programManager)
        ->patchJson("/api/v1/programs/{$program->id}/status", ['status' => ProgramStatus::Approved->value])
        ->assertOk();

    Notification::assertNotSentTo($this->management, ProgramAwaitingApproval::class);
});
