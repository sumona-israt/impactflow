<?php

namespace App\Http\Requests\Workflow;

use App\Enums\WorkflowDecision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordWorkflowActionRequest extends FormRequest
{
    /**
     * There's no static permission to check here — who may act depends on
     * which step the instance is currently on (workflow_steps.role_required),
     * which is only known once the instance is loaded. WorkflowService::act()
     * enforces that role check itself and throws a 422 if it fails; this
     * request only needs the caller to be authenticated (already required by
     * the route middleware).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(array_map(fn (WorkflowDecision $d) => $d->value, WorkflowDecision::cases()))],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
