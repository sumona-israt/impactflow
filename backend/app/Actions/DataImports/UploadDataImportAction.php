<?php

namespace App\Actions\DataImports;

use App\Enums\DataImportStatus;
use App\Enums\ImportEntityType;
use App\Models\DataImport;
use App\Services\Import\DataImportSpreadsheetReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UploadDataImportAction
{
    public function __construct(private readonly DataImportSpreadsheetReader $reader) {}

    public function execute(UploadedFile $file, ImportEntityType $entityType): DataImport
    {
        // Same disk/visibility convention as expense receipts — never
        // web-served directly, only ever read back server-side.
        $path = $file->store('imports', 'local');

        $headers = $this->reader->headers(Storage::disk('local')->path($path));

        return DataImport::create([
            'entity_type' => $entityType,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by' => Auth::id(),
            'status' => DataImportStatus::Uploaded,
            'detected_headers' => $headers,
        ]);
    }
}
