<?php

namespace App\Http\Requests\Programs;

use App\Enums\PermissionEnum;
use App\Enums\ProgramStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgramStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::UpdateProgramStatus->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_map(fn (ProgramStatus $s) => $s->value, ProgramStatus::cases()))],
        ];
    }
}
