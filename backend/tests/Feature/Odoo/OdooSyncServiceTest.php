<?php

use App\Exceptions\OdooConnectionException;
use App\Jobs\OdooSyncJob;
use App\Models\Employee;
use App\Models\OdooConnection;
use App\Models\OdooMapping;
use App\Models\OdooSyncLog;
use App\Models\Program;
use App\Services\Odoo\OdooSyncService;

test('a successful sync creates a mapping row and sets the denormalized column', function () {
    $program = Program::factory()->create();

    app(OdooSyncService::class)->sync(Program::class, $program->id);

    $mapping = OdooMapping::where('entity_type', Program::class)->where('local_id', $program->id)->first();
    expect($mapping)->not->toBeNull();
    expect($program->fresh()->odoo_project_id)->toBe($mapping->odoo_id);

    $log = OdooSyncLog::where('entity_type', Program::class)->where('local_id', $program->id)->first();
    expect($log->status)->toBe('success');
});

test('an employee sync sets odoo_employee_id, not a mapping-less state', function () {
    $employee = Employee::factory()->create();

    app(OdooSyncService::class)->sync(Employee::class, $employee->id);

    expect($employee->fresh()->odoo_employee_id)->not->toBeNull();
});

test('re-creating a mapping after it is removed resolves to the same deterministic fake Odoo id', function () {
    $program = Program::factory()->create();

    app(OdooSyncService::class)->sync(Program::class, $program->id);
    $firstId = OdooMapping::where('local_id', $program->id)->first()->odoo_id;

    OdooMapping::where('local_id', $program->id)->delete();

    app(OdooSyncService::class)->sync(Program::class, $program->id);
    $secondId = OdooMapping::where('local_id', $program->id)->first()->odoo_id;

    expect($secondId)->toBe($firstId);
});

test('a forced mock failure fails the sync and records the error on the log row', function () {
    config(['odoo.mock_failure_rate' => 1]);

    $program = Program::factory()->create();

    expect(fn () => app(OdooSyncService::class)->sync(Program::class, $program->id))
        ->toThrow(OdooConnectionException::class);

    $log = OdooSyncLog::where('entity_type', Program::class)->where('local_id', $program->id)->first();
    expect($log->status)->toBe('failed');
    expect($log->error_message)->not->toBeNull();
    expect(OdooMapping::where('local_id', $program->id)->exists())->toBeFalse();
});

test("OdooSyncJob's failed() hook marks the log row failed with the exhaustion error", function () {
    config(['odoo.mock_failure_rate' => 1]);

    $program = Program::factory()->create();

    // Seed a log row the way a real (failing) attempt would, then simulate
    // the queue exhausting all retries and invoking the job's failed() hook.
    expect(fn () => app(OdooSyncService::class)->sync(Program::class, $program->id))
        ->toThrow(OdooConnectionException::class);

    (new OdooSyncJob(Program::class, $program->id))->failed(new Exception('All retries exhausted'));

    $log = OdooSyncLog::where('entity_type', Program::class)->where('local_id', $program->id)->first();
    expect($log->status)->toBe('failed');
    expect($log->error_message)->toBe('All retries exhausted');
});

test('sync is skipped, not failed, while the Odoo connection is paused', function () {
    OdooConnection::query()->delete();
    OdooConnection::create(['name' => 'primary', 'is_active' => false]);

    $program = Program::factory()->create();

    app(OdooSyncService::class)->sync(Program::class, $program->id);

    $log = OdooSyncLog::where('entity_type', Program::class)->where('local_id', $program->id)->first();
    expect($log->status)->toBe('skipped');
    expect(OdooMapping::where('local_id', $program->id)->exists())->toBeFalse();
});
