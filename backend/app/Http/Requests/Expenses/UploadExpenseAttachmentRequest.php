<?php

namespace App\Http\Requests\Expenses;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class UploadExpenseAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->can(PermissionEnum::UpdateExpense->value)) {
            return false;
        }

        return $this->user()->hasRole(RoleEnum::SuperAdmin->value)
            || $this->route('expense')->submitted_by === $this->user()->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
