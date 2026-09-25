<?php

namespace App\Actions\Expenses;

use App\Models\Expense;
use App\Models\ExpenseAttachment;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class UploadExpenseAttachmentAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(Expense $expense, UploadedFile $file): ExpenseAttachment
    {
        // Stored on the default 'local' disk (storage/app/private — never
        // web-served directly) under a per-expense, non-guessable path.
        // Download only ever happens through the authenticated route.
        $path = $file->store("expense-attachments/{$expense->id}", 'local');

        $attachment = ExpenseAttachment::create([
            'expense_id' => $expense->id,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => Auth::id(),
        ]);

        $this->auditLogger->log('expense.attachment_uploaded', Expense::class, $expense->id, [], [
            'file_name' => $attachment->original_name,
        ]);

        return $attachment;
    }
}
