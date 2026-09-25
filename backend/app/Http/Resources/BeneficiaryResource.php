<?php

namespace App\Http\Resources;

use App\Models\Beneficiary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full detail view — includes sensitive fields. Only returned to callers
 * with `beneficiaries.view`, per docs/database-design.md §4. List endpoints
 * use BeneficiaryListResource instead.
 *
 * @mixin Beneficiary
 */
class BeneficiaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'district' => $this->district,
            'upazila' => $this->upazila,
            'status' => $this->status,
            'registration_date' => $this->registration_date,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
