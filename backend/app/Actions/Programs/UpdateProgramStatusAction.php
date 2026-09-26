<?php

namespace App\Actions\Programs;

use App\Enums\ProgramStatus;
use App\Events\ProgramApproved;
use App\Models\Program;
use App\Services\Audit\AuditLogger;
use Illuminate\Validation\ValidationException;

class UpdateProgramStatusAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(Program $program, ProgramStatus $status): Program
    {
        $current = $program->status;

        if ($status !== $current && ! in_array($status, $current->allowedNextStatuses(), true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot move a program from \"{$current->value}\" to \"{$status->value}\".",
            ]);
        }

        $program->update(['status' => $status]);

        if ($status !== $current) {
            $this->auditLogger->log(
                'program.status_changed',
                Program::class,
                $program->id,
                ['status' => $current->value],
                ['status' => $status->value],
            );

            if ($status === ProgramStatus::Approved) {
                ProgramApproved::dispatch($program);
            }
        }

        return $program;
    }
}
