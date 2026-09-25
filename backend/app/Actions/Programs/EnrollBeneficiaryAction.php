<?php

namespace App\Actions\Programs;

use App\Enums\BeneficiaryProgramStatus;
use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\Program;
use App\Services\Audit\AuditLogger;
use Illuminate\Validation\ValidationException;

class EnrollBeneficiaryAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(Program $program, Beneficiary $beneficiary): BeneficiaryProgram
    {
        if ($program->beneficiaryPrograms()->where('beneficiary_id', $beneficiary->id)->exists()) {
            throw ValidationException::withMessages([
                'beneficiary_id' => 'This beneficiary is already enrolled in this program.',
            ]);
        }

        $enrollment = BeneficiaryProgram::create([
            'beneficiary_id' => $beneficiary->id,
            'program_id' => $program->id,
            'enrolled_at' => now(),
            'status' => BeneficiaryProgramStatus::Enrolled,
        ]);

        $this->auditLogger->log(
            'program.beneficiary_enrolled',
            Program::class,
            $program->id,
            [],
            ['beneficiary_id' => $beneficiary->id, 'beneficiary_name' => $beneficiary->full_name],
        );

        return $enrollment;
    }
}
