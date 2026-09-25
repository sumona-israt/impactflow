<?php

namespace App\Actions\Beneficiaries;

use App\Enums\BeneficiaryStatus;
use App\Models\Beneficiary;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Auth;

class CreateBeneficiaryAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(array $attributes): Beneficiary
    {
        // See CreateProgramAction for why this is explicit, not a DB default.
        $beneficiary = Beneficiary::create([
            'status' => BeneficiaryStatus::Active,
            ...$attributes,
            'created_by' => Auth::id(),
        ]);

        $this->auditLogger->logCreated($beneficiary, 'beneficiary.created');

        return $beneficiary;
    }
}
