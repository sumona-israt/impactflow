<?php

namespace App\Actions\DataImports;

use App\Enums\DataImportRowStatus;
use App\Enums\DataImportStatus;
use App\Models\Beneficiary;
use App\Models\DataImport;
use App\Services\DataQuality\BeneficiaryFieldNormalizer;
use App\Services\DataQuality\BeneficiaryImportRowValidator;
use App\Services\Import\DataImportSpreadsheetReader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PreviewDataImportAction
{
    public function __construct(
        private readonly DataImportSpreadsheetReader $reader,
        private readonly BeneficiaryImportRowValidator $validator,
    ) {}

    public function execute(DataImport $dataImport): DataImport
    {
        // Re-running preview after a remap replaces the staged rows.
        $dataImport->rows()->delete();

        $mapping = $dataImport->column_mapping ?? [];
        $seenInBatch = [];
        $counts = ['valid' => 0, 'invalid' => 0, 'duplicate' => 0];
        $rowsToInsert = [];

        foreach ($this->reader->rows(Storage::disk('local')->path($dataImport->file_path)) as $rowNumber => $sourceRow) {
            $attributes = $this->mapRow($sourceRow, $mapping);
            $result = $this->validator->validate($attributes);

            if ($result->fails()) {
                $counts['invalid']++;
                $rowsToInsert[] = $this->rowAttributes($dataImport, $rowNumber, $attributes, DataImportRowStatus::Invalid, $result->errors()->toArray());

                continue;
            }

            $key = $this->duplicateKey($attributes);
            $isDuplicate = $key !== null && (isset($seenInBatch[$key]) || $this->matchesExistingBeneficiary($attributes));

            if ($key !== null) {
                $seenInBatch[$key] = true;
            }

            if ($isDuplicate) {
                $counts['duplicate']++;
                $rowsToInsert[] = $this->rowAttributes($dataImport, $rowNumber, $attributes, DataImportRowStatus::Duplicate);
            } else {
                $counts['valid']++;
                $rowsToInsert[] = $this->rowAttributes($dataImport, $rowNumber, $attributes, DataImportRowStatus::Valid);
            }
        }

        foreach (array_chunk($rowsToInsert, 200) as $chunk) {
            $dataImport->rows()->insert($chunk);
        }

        $dataImport->update([
            'status' => DataImportStatus::Previewed,
            'total_rows' => count($rowsToInsert),
            'valid_rows' => $counts['valid'],
            'duplicate_rows' => $counts['duplicate'],
            'invalid_rows' => $counts['invalid'],
        ]);

        return $dataImport;
    }

    /**
     * @param  array<string, mixed>  $sourceRow
     * @param  array<string, string>  $mapping
     * @return array<string, mixed>
     */
    private function mapRow(array $sourceRow, array $mapping): array
    {
        $attributes = [];

        foreach ($mapping as $field => $sourceColumn) {
            $attributes[$field] = $sourceRow[$sourceColumn] ?? null;
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function duplicateKey(array $attributes): ?string
    {
        $name = BeneficiaryFieldNormalizer::name($attributes['full_name'] ?? null);
        $phone = BeneficiaryFieldNormalizer::phone($attributes['phone'] ?? null);

        return ($name === '' || $phone === '') ? null : "{$name}|{$phone}";
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function matchesExistingBeneficiary(array $attributes): bool
    {
        $name = BeneficiaryFieldNormalizer::name($attributes['full_name'] ?? null);
        $phone = BeneficiaryFieldNormalizer::phone($attributes['phone'] ?? null);

        if ($name === '' || $phone === '') {
            return false;
        }

        return Beneficiary::query()
            ->whereRaw('lower(trim(full_name)) = ?', [$name])
            ->get(['phone'])
            ->contains(fn (Beneficiary $b) => BeneficiaryFieldNormalizer::phone($b->phone) === $phone);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>|null  $errors
     * @return array<string, mixed>
     */
    private function rowAttributes(DataImport $dataImport, int $rowNumber, array $attributes, DataImportRowStatus $status, ?array $errors = null): array
    {
        return [
            'id' => (string) Str::uuid(),
            'data_import_id' => $dataImport->id,
            'row_number' => $rowNumber,
            'raw_data' => json_encode($attributes),
            'status' => $status->value,
            'errors' => $errors !== null ? json_encode($errors) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
