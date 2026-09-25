<?php

namespace App\Models;

use App\Contracts\BudgetConstrained;
use App\Contracts\Workflowable;
use App\Enums\ExpenseStatus;
use App\Enums\WorkflowInstanceStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Validation\ValidationException;

class Expense extends Model implements BudgetConstrained, Workflowable
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'program_id', 'category_id', 'submitted_by', 'amount', 'currency',
        'expense_date', 'description', 'status',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
            'status' => ExpenseStatus::class,
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ExpenseAttachment::class);
    }

    public function workflowInstances(): MorphMany
    {
        return $this->morphMany(WorkflowInstance::class, 'entity')->latest('id');
    }

    public function latestWorkflowInstance(): ?WorkflowInstance
    {
        return $this->workflowInstances()->first();
    }

    /**
     * The engine calls this after recording a decision — see
     * App\Contracts\Workflowable and docs/database-design.md §8.
     */
    public function syncWorkflowStatus(WorkflowInstance $instance): void
    {
        $this->status = match ($instance->status) {
            WorkflowInstanceStatus::InProgress => ExpenseStatus::from($instance->currentStep->name),
            WorkflowInstanceStatus::Approved => ExpenseStatus::Approved,
            WorkflowInstanceStatus::Rejected => ExpenseStatus::Rejected,
            WorkflowInstanceStatus::Returned => ExpenseStatus::Draft,
        };

        $this->save();
    }

    /**
     * Finance is the budget gatekeeper (product brief §14/§4) — checked once,
     * at final approval, against programs.budget minus already-approved
     * expenses for that program. Skipped entirely if the program has no
     * budget configured, since there's nothing to enforce.
     */
    public function assertWithinBudget(): void
    {
        $program = $this->program;

        if ($program->budget === null) {
            return;
        }

        $alreadyApproved = self::query()
            ->where('program_id', $program->id)
            ->where('status', ExpenseStatus::Approved)
            ->where('id', '!=', $this->id)
            ->sum('amount');

        $remaining = $program->budget - $alreadyApproved;

        if ($this->amount > $remaining) {
            throw ValidationException::withMessages([
                'amount' => "Approving this expense (৳{$this->amount}) would exceed the program's remaining budget (৳{$remaining}).",
            ]);
        }
    }
}
