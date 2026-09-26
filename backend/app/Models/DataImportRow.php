<?php

namespace App\Models;

use App\Enums\DataImportRowStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataImportRow extends Model
{
    use HasUuids;

    protected $fillable = [
        'data_import_id', 'row_number', 'raw_data', 'status', 'errors', 'beneficiary_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => DataImportRowStatus::class,
            'raw_data' => 'array',
            'errors' => 'array',
        ];
    }

    public function dataImport(): BelongsTo
    {
        return $this->belongsTo(DataImport::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
