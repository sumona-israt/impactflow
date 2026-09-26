<?php

namespace App\Actions\Workflow;

use App\Contracts\BudgetConstrained;
use App\Enums\WorkflowDecision;
use App\Enums\WorkflowInstanceStatus;
use App\Events\ExpenseApproved;
use App\Models\Expense;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Services\Audit\AuditLogger;
use App\Services\Workflow\WorkflowService;

/**
 * Sits above the generic WorkflowService and stays generic itself — any
 * budget (or other future) constraint is expressed as a contract the entity
 * implements (see App\Contracts\BudgetConstrained), not as entity-specific
 * logic here. This is the single entry point every workflow action goes
 * through, for any entity_type.
 */
class RecordWorkflowActionAction
{
    public function __construct(
        private readonly WorkflowService $workflowService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(WorkflowInstance $instance, User $actor, WorkflowDecision $decision, ?string $comment): WorkflowInstance
    {
        if ($decision === WorkflowDecision::Approve && $this->isFinalStep($instance)) {
            $entity = $instance->entity;

            if ($entity instanceof BudgetConstrained) {
                $entity->assertWithinBudget();
            }
        }

        $before = ['status' => $instance->status->value, 'step' => $instance->currentStep?->name];

        $instance = $this->workflowService->act($instance, $actor, $decision, $comment);

        $this->auditLogger->log(
            'workflow.'.$decision->value,
            $instance->entity_type,
            $instance->entity_id,
            $before,
            ['status' => $instance->status->value, 'step' => $instance->currentStep?->name],
        );

        // Odoo integration hook (see docs/odoo-integration.md) — a named
        // exception to this class's otherwise entity-agnostic design,
        // mirroring the BudgetConstrained instanceof check above.
        if ($instance->status === WorkflowInstanceStatus::Approved && $instance->entity instanceof Expense) {
            ExpenseApproved::dispatch($instance->entity);
        }

        return $instance;
    }

    private function isFinalStep(WorkflowInstance $instance): bool
    {
        return ! $instance->workflow->steps()
            ->where('sequence', '>', $instance->currentStep->sequence)
            ->exists();
    }
}
