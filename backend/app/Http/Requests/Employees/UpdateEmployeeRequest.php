<?php

namespace App\Http\Requests\Employees;

use App\Enums\EmployeeStatus;
use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::UpdateEmployee->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'user_id' => ['nullable', Rule::exists('users', 'id')],
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')],
            'position' => ['nullable', 'string', 'max:255'],
            'joining_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(array_map(fn (EmployeeStatus $s) => $s->value, EmployeeStatus::cases()))],
        ];
    }
}
