<?php

namespace App\Models;

use App\Enums\DataImportStatus;
use App\Enums\ImportEntityType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataImport extends Model
{
    use HasUuids;

    protected $fillable = [
        'entity_type', 'file_path', 'original_name', 'uploaded_by', 'status',
        'detected_headers', 'column_mapping', 'total_rows', 'valid_rows',
        'duplicate_rows', 'invalid_rows', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'entity_type' => ImportEntityType::class,
            'status' => DataImportStatus::class,
            'detected_headers' => 'array',
            'column_mapping' => 'array',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(DataImportRow::class);
    }
}
