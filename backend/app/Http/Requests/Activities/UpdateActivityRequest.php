<?php

namespace App\Http\Requests\Activities;

use App\Enums\ActivityStatus;
use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::UpdateActivity->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scheduled_at' => ['sometimes', 'required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_map(fn (ActivityStatus $s) => $s->value, ActivityStatus::cases()))],
        ];
    }
}
