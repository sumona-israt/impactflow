<?php

namespace App\Models;

use App\Enums\WorkflowInstanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowInstance extends Model
{
    protected $fillable = ['workflow_id', 'entity_type', 'entity_id', 'current_step_id', 'status'];

    protected function casts(): array
    {
        return ['status' => WorkflowInstanceStatus::class];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class)->orderBy('created_at');
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
