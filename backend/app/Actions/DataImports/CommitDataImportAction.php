<?php

namespace App\Actions\DataImports;

use App\Enums\DataImportStatus;
use App\Jobs\Import\ProcessDataImportCommit;
use App\Models\DataImport;

class CommitDataImportAction
{
    public function execute(DataImport $dataImport): DataImport
    {
        $dataImport->update(['status' => DataImportStatus::Committing]);

        // On the `sync` queue connection (as in tests) this runs to
        // completion inline, but always against a freshly-unserialized job
        // instance (see SerializesModels) — refresh so the caller sees the
        // real end state (committed/failed) rather than this stale in-memory
        // "committing" snapshot.
        ProcessDataImportCommit::dispatch($dataImport);

        return $dataImport->fresh();
    }
}
