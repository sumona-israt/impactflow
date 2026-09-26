<?php

namespace App\Jobs\Import;

use App\Actions\Beneficiaries\CreateBeneficiaryAction;
use App\Enums\DataImportRowStatus;
use App\Enums\DataImportStatus;
use App\Models\DataImport;
use App\Models\DataImportRow;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Runs on the `worker` container (docker-compose.yml) via the redis queue.
 * Reuses CreateBeneficiaryAction per row so duplicate detection and audit
 * logging happen in exactly one place for both single-create and bulk
 * import — see docs/database-design.md §9.
 */
class ProcessDataImportCommit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public DataImport $dataImport) {}

    public function handle(CreateBeneficiaryAction $createBeneficiary, AuditLogger $auditLogger): void
    {
        // The job runs outside any HTTP request, so there's no authenticated
        // user by default — act as the uploader for attribution (audit log,
        // Beneficiary.created_by). setUser() (not onceUsingId(), which only
        // SessionGuard implements) works on every guard, including the
        // stateless RequestGuard Sanctum installs as the default guard.
        Auth::setUser(User::findOrFail($this->dataImport->uploaded_by));

        try {
            $created = DB::transaction(function () use ($createBeneficiary) {
                $rows = $this->dataImport->rows()
                    ->whereIn('status', [DataImportRowStatus::Valid, DataImportRowStatus::Duplicate])
                    ->whereNull('beneficiary_id')
                    ->get();

                $rows->each(function (DataImportRow $row) use ($createBeneficiary) {
                    $beneficiary = $createBeneficiary->execute($row->raw_data);
                    $row->update(['beneficiary_id' => $beneficiary->id]);
                });

                return $rows->count();
            });

            $this->dataImport->update(['status' => DataImportStatus::Committed]);

            $auditLogger->log('data-import.committed', DataImport::class, $this->dataImport->id, [], ['created' => $created]);
        } catch (Throwable $e) {
            $this->dataImport->update([
                'status' => DataImportStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
