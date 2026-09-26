<?php

namespace App\Actions\DataImports;

use App\Enums\DataImportStatus;
use App\Models\DataImport;
use Illuminate\Validation\ValidationException;

class UpdateImportMappingAction
{
    /**
     * Beneficiary fields an import can populate — mirrors
     * StoreBeneficiaryRequest's field list minus `created_by` (system-set).
     *
     * @var list<string>
     */
    public const MAPPABLE_FIELDS = [
        'full_name', 'date_of_birth', 'gender', 'phone', 'email', 'address',
        'district', 'upazila', 'status', 'registration_date',
        'emergency_contact_name', 'emergency_contact_phone', 'notes',
    ];

    /** @var list<string> */
    public const REQUIRED_FIELDS = ['full_name', 'registration_date'];

    /**
     * @param  array<string, string|null>  $mapping  Beneficiary field => source column header
     */
    public function execute(DataImport $dataImport, array $mapping): DataImport
    {
        $mapping = array_filter(
            $mapping,
            fn (?string $column, string $field) => $column !== null && $column !== '' && in_array($field, self::MAPPABLE_FIELDS, true),
            ARRAY_FILTER_USE_BOTH,
        );

        $missing = array_diff(self::REQUIRED_FIELDS, array_keys($mapping));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'mapping' => 'Required fields must be mapped: '.implode(', ', $missing),
            ]);
        }

        $dataImport->update([
            'column_mapping' => $mapping,
            'status' => DataImportStatus::Mapped,
        ]);

        return $dataImport;
    }
}
