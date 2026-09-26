<?php

namespace App\Models;

use App\Enums\ReportFormat;
use App\Enums\ReportType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasUuids;

    protected $fillable = [
        'type', 'format', 'parameters', 'file_path', 'generated_by', 'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ReportType::class,
            'format' => ReportFormat::class,
            'parameters' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
