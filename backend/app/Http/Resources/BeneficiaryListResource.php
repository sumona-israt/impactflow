<?php

namespace App\Http\Resources;

use App\Models\Beneficiary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Deliberately excludes phone/DOB/address/email — see BeneficiaryResource.
 *
 * @mixin Beneficiary
 */
class BeneficiaryListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'district' => $this->district,
            'upazila' => $this->upazila,
            'status' => $this->status,
            'registration_date' => $this->registration_date,
        ];
    }
}
