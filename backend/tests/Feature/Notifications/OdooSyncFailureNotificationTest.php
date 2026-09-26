<?php

use App\Enums\RoleEnum;
use App\Jobs\OdooSyncJob;
use App\Models\Program;
use App\Models\User;
use App\Notifications\OdooSyncFailed;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);

    $this->management = User::factory()->create();
    $this->management->assignRole(RoleEnum::Management->value);

    $this->fieldOfficer = User::factory()->create();
    $this->fieldOfficer->assignRole(RoleEnum::FieldOfficer->value);
});

test('exhausting all retries notifies management with the error, not other roles', function () {
    Notification::fake();

    $program = Program::factory()->create();

    (new OdooSyncJob(Program::class, $program->id))->failed(new Exception('All retries exhausted'));

    Notification::assertSentTo(
        $this->management,
        OdooSyncFailed::class,
        fn (OdooSyncFailed $notification) => $notification->toArray($this->management)['message']
            === "Syncing Program #{$program->id} to Odoo failed: All retries exhausted",
    );
    Notification::assertNotSentTo($this->fieldOfficer, OdooSyncFailed::class);
});
