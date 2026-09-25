<?php

namespace App\Http\Requests\Assets;

use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::CreateAsset->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'serial_number' => ['nullable', 'string', 'max:120', 'unique:assets,serial_number'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_value' => ['nullable', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'condition' => ['nullable', 'string', 'max:60'],
        ];
    }
}
