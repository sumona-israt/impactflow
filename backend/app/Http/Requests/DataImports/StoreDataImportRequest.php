<?php

namespace App\Http\Requests\DataImports;

use App\Enums\ImportEntityType;
use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDataImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::CreateDataImport->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
            'entity_type' => ['required', Rule::in(array_map(fn (ImportEntityType $t) => $t->value, ImportEntityType::cases()))],
        ];
    }
}
