<?php

namespace App\Services\DataQuality;

use App\Enums\BeneficiaryStatus;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidatorContract;

/**
 * Mirrors App\Http\Requests\Beneficiaries\StoreBeneficiaryRequest::rules() —
 * duplicated rather than shared because FormRequest rules aren't reusable
 * outside an HTTP request cycle (no request to bind rule closures to here).
 */
class BeneficiaryImportRowValidator
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function validate(array $attributes): ValidatorContract
    {
        return Validator::make($attributes, [
            'full_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:120'],
            'upazila' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(array_map(fn (BeneficiaryStatus $s) => $s->value, BeneficiaryStatus::cases()))],
            'registration_date' => ['required', 'date'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
