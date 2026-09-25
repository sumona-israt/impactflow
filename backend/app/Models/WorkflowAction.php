<?php

namespace App\Models;

use App\Enums\WorkflowDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowAction extends Model
{
    public $timestamps = false;

    protected $fillable = ['workflow_instance_id', 'step_id', 'actor_id', 'action', 'comment', 'created_at'];

    protected function casts(): array
    {
        return [
            'action' => WorkflowDecision::class,
            'created_at' => 'datetime',
        ];
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'step_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
