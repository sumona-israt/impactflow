<?php

namespace App\Actions\Programs;

use App\Models\Program;
use App\Services\Audit\AuditLogger;

class UpdateProgramAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(Program $program, array $attributes): Program
    {
        $program->update($attributes);

        if ($program->wasChanged()) {
            $this->auditLogger->logUpdated($program, 'program.updated');
        }

        return $program;
    }
}
