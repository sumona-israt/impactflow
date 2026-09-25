<?php

namespace App\Http\Requests\Programs;

use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnrollBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::EnrollBeneficiaryInProgram->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'beneficiary_id' => ['required', Rule::exists('beneficiaries', 'id')],
        ];
    }
}
