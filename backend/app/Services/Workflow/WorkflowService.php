<?php

namespace App\Services\Workflow;

use App\Contracts\Workflowable;
use App\Enums\RoleEnum;
use App\Enums\WorkflowDecision;
use App\Enums\WorkflowInstanceStatus;
use App\Models\ApprovalWorkflow;
use App\Models\User;
use App\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * The generic approval workflow engine — see docs/database-design.md §8.
 * Knows nothing about Expense or any other specific entity; entities that
 * want their status kept in sync implement App\Contracts\Workflowable and
 * the caller (e.g. WorkflowInstanceController) invokes it after act().
 */
class WorkflowService
{
    public function start(Model&Workflowable $entity, string $workflowName): WorkflowInstance
    {
        $workflow = ApprovalWorkflow::where('name', $workflowName)->firstOrFail();
        $firstStep = $workflow->steps()->orderBy('sequence')->firstOrFail();

        $instance = WorkflowInstance::create([
            'workflow_id' => $workflow->id,
            'entity_type' => $entity::class,
            'entity_id' => $entity->getKey(),
            'current_step_id' => $firstStep->id,
            'status' => WorkflowInstanceStatus::InProgress,
        ]);

        $entity->syncWorkflowStatus($instance->fresh(['currentStep']));

        return $instance;
    }

    public function act(WorkflowInstance $instance, User $actor, WorkflowDecision $decision, ?string $comment = null): WorkflowInstance
    {
        if ($instance->status !== WorkflowInstanceStatus::InProgress) {
            throw ValidationException::withMessages([
                'action' => 'This workflow has already been completed.',
            ]);
        }

        $step = $instance->currentStep;

        if (! $actor->hasRole($step->role_required) && ! $actor->hasRole(RoleEnum::SuperAdmin->value)) {
            throw ValidationException::withMessages([
                'action' => "Only a {$step->role_required} may act on this step.",
            ]);
        }

        $instance->actions()->create([
            'step_id' => $step->id,
            'actor_id' => $actor->id,
            'action' => $decision,
            'comment' => $comment,
            'created_at' => now(),
        ]);

        match ($decision) {
            WorkflowDecision::Approve => $this->advance($instance),
            WorkflowDecision::Reject => $instance->update(['status' => WorkflowInstanceStatus::Rejected, 'current_step_id' => null]),
            WorkflowDecision::Return => $instance->update(['status' => WorkflowInstanceStatus::Returned, 'current_step_id' => null]),
        };

        $instance = $instance->fresh(['currentStep']);

        $entity = $instance->entity;

        if ($entity instanceof Workflowable) {
            $entity->syncWorkflowStatus($instance);
        }

        return $instance;
    }

    private function advance(WorkflowInstance $instance): void
    {
        $nextStep = $instance->workflow->steps()
            ->where('sequence', '>', $instance->currentStep->sequence)
            ->orderBy('sequence')
            ->first();

        if ($nextStep) {
            $instance->update(['current_step_id' => $nextStep->id]);

            return;
        }

        $instance->update(['status' => WorkflowInstanceStatus::Approved, 'current_step_id' => null]);
    }
}
