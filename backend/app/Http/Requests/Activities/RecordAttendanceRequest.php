<?php

namespace App\Http\Requests\Activities;

use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

class RecordAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::RecordActivityAttendance->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attendance' => ['required', 'array', 'min:1'],
            'attendance.*' => ['required', 'boolean'],
        ];
    }
}
