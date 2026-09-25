<?php

namespace App\Actions\Programs;

use App\Enums\BeneficiaryProgramStatus;
use App\Models\BeneficiaryProgram;
use App\Models\Program;
use App\Services\Audit\AuditLogger;

class UnenrollBeneficiaryAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(BeneficiaryProgram $enrollment): BeneficiaryProgram
    {
        $enrollment->update(['status' => BeneficiaryProgramStatus::Withdrawn]);

        $this->auditLogger->log(
            'program.beneficiary_unenrolled',
            Program::class,
            $enrollment->program_id,
            [],
            ['beneficiary_id' => $enrollment->beneficiary_id],
        );

        return $enrollment;
    }
}
