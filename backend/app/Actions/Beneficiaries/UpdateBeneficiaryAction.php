<?php

namespace App\Actions\Beneficiaries;

use App\Models\Beneficiary;
use App\Services\Audit\AuditLogger;
use App\Services\DataQuality\BeneficiaryDuplicateDetector;

class UpdateBeneficiaryAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly BeneficiaryDuplicateDetector $duplicateDetector,
    ) {}

    public function execute(Beneficiary $beneficiary, array $attributes): Beneficiary
    {
        $beneficiary->update($attributes);

        if ($beneficiary->wasChanged()) {
            $this->auditLogger->logUpdated($beneficiary, 'beneficiary.updated');
        }

        if ($beneficiary->wasChanged(['full_name', 'phone'])) {
            $this->duplicateDetector->detect($beneficiary);
        }

        return $beneficiary;
    }
}
