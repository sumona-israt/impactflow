<?php

namespace App\Http\Requests\Programs;

use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::CreateProgram->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', Rule::exists('program_categories', 'id')],
            'manager_id' => ['nullable', Rule::exists('users', 'id')],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')],
            'district' => ['nullable', 'string', 'max:120'],
            'upazila' => ['nullable', 'string', 'max:120'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'target_beneficiaries' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
