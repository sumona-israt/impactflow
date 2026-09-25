<?php

namespace App\Actions\Programs;

use App\Enums\ProgramStatus;
use App\Models\Program;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Auth;

class CreateProgramAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(array $attributes): Program
    {
        // Explicit rather than relying on the DB column defaults for
        // status/progress: Eloquent doesn't reload defaults-only columns
        // after create(), so the in-memory model (and the API response
        // built from it) would otherwise show them as null (see the
        // identical is_active bug fixed in Phase 2's CreateUserAction).
        $program = Program::create([
            'status' => ProgramStatus::Draft,
            'progress' => 0,
            ...$attributes,
            'created_by' => Auth::id(),
        ]);

        $this->auditLogger->logCreated($program, 'program.created');

        return $program;
    }
}
