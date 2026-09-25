<?php

namespace App\Http\Requests\Expenses;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->can(PermissionEnum::UpdateExpense->value)) {
            return false;
        }

        // A draft belongs to whoever submitted it — Super Admin excepted,
        // matching "Super Administrator: Everything" elsewhere in the app.
        return $this->user()->hasRole(RoleEnum::SuperAdmin->value)
            || $this->route('expense')->submitted_by === $this->user()->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', Rule::exists('expense_categories', 'id')],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'size:3'],
            'expense_date' => ['sometimes', 'required', 'date'],
            'description' => ['nullable', 'string'],
        ];
    }
}
