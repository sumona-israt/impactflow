<?php

namespace App\Http\Resources;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Expense */
class ExpenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'program' => $this->whenLoaded('program', fn () => [
                'id' => $this->program->id,
                'name' => $this->program->name,
            ]),
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null),
            'submitter' => $this->whenLoaded('submitter', fn () => [
                'id' => $this->submitter->id,
                'name' => $this->submitter->name,
            ]),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'expense_date' => $this->expense_date,
            'description' => $this->description,
            'status' => $this->status,
            'attachments' => ExpenseAttachmentResource::collection($this->whenLoaded('attachments')),
            'workflow_instance' => $this->whenLoaded(
                'workflowInstances',
                fn () => $this->workflowInstances->isNotEmpty()
                    ? new WorkflowInstanceResource($this->workflowInstances->first())
                    : null,
            ),
            'created_at' => $this->created_at,
        ];
    }
}
