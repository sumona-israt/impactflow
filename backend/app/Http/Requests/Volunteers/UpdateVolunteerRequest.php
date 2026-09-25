<?php

namespace App\Http\Requests\Volunteers;

use App\Enums\PermissionEnum;
use App\Enums\VolunteerAvailability;
use App\Enums\VolunteerStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVolunteerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::UpdateVolunteer->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:60'],
            'availability' => ['nullable', Rule::in(array_map(fn (VolunteerAvailability $a) => $a->value, VolunteerAvailability::cases()))],
            'status' => ['nullable', Rule::in(array_map(fn (VolunteerStatus $s) => $s->value, VolunteerStatus::cases()))],
        ];
    }
}
