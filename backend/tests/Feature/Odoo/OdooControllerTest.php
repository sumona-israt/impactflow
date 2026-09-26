<?php

use App\Enums\RoleEnum;
use App\Jobs\OdooSyncJob;
use App\Models\Beneficiary;
use App\Models\OdooConnection;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);

    $this->management = User::factory()->create();
    $this->management->assignRole(RoleEnum::Management->value);

    $this->hrAdminOfficer = User::factory()->create();
    $this->hrAdminOfficer->assignRole(RoleEnum::HrAdminOfficer->value);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole(RoleEnum::SuperAdmin->value);
});

test('management can view status and sync logs but not update config', function () {
    $this->actingAs($this->management)->getJson('/api/v1/odoo/status')->assertOk();
    $this->actingAs($this->management)->getJson('/api/v1/odoo/sync-logs')->assertOk();
    $this->actingAs($this->management)->getJson('/api/v1/odoo/config')->assertOk();

    $this->actingAs($this->management)
        ->putJson('/api/v1/odoo/config', ['is_active' => false])
        ->assertForbidden();
});

test('an hr admin officer is forbidden from every Odoo endpoint', function () {
    $this->actingAs($this->hrAdminOfficer)->getJson('/api/v1/odoo/status')->assertForbidden();
    $this->actingAs($this->hrAdminOfficer)->getJson('/api/v1/odoo/sync-logs')->assertForbidden();
    $this->actingAs($this->hrAdminOfficer)->getJson('/api/v1/odoo/config')->assertForbidden();
    $this->actingAs($this->hrAdminOfficer)
        ->postJson('/api/v1/odoo/sync/beneficiary/1/retry')
        ->assertForbidden();
});

test('a super admin can update Odoo config via the Gate::before bypass', function () {
    $this->actingAs($this->superAdmin)
        ->putJson('/api/v1/odoo/config', ['is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    expect(OdooConnection::first()->is_active)->toBeFalse();
});

test('retrying re-dispatches an Odoo sync job for the given entity and id', function () {
    Queue::fake();

    $beneficiary = Beneficiary::factory()->create();

    $this->actingAs($this->management)
        ->postJson("/api/v1/odoo/sync/beneficiary/{$beneficiary->id}/retry")
        ->assertOk();

    Queue::assertPushed(OdooSyncJob::class, fn (OdooSyncJob $job) => $job->entityType === Beneficiary::class
        && $job->localId === $beneficiary->id);
});
