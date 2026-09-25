<?php

namespace App\Actions\Beneficiaries;

use App\Models\Beneficiary;
use App\Services\Audit\AuditLogger;

class UpdateBeneficiaryAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(Beneficiary $beneficiary, array $attributes): Beneficiary
    {
        $beneficiary->update($attributes);

        if ($beneficiary->wasChanged()) {
            $this->auditLogger->logUpdated($beneficiary, 'beneficiary.updated');
        }

        return $beneficiary;
    }
}
