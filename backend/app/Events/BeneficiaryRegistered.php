<?php

namespace App\Events;

use App\Models\Beneficiary;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a beneficiary is created (see
 * App\Actions\Beneficiaries\CreateBeneficiaryAction). Consumed by
 * App\Listeners\DispatchBeneficiaryRegisteredToOdoo.
 */
class BeneficiaryRegistered
{
    use Dispatchable;

    public function __construct(public readonly Beneficiary $beneficiary) {}
}
