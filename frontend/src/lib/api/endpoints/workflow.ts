import { apiFetch } from "@/lib/api/client";
import type { ApprovalWorkflowDefinition, WorkflowDecision, WorkflowInstance } from "@/types/finance";

interface Envelope<T> {
  data: T;
}

export function listWorkflows(): Promise<ApprovalWorkflowDefinition[]> {
  return apiFetch<Envelope<ApprovalWorkflowDefinition[]>>("/api/v1/workflows").then((res) => res.data);
}

export function recordWorkflowAction(
  instanceId: number,
  action: WorkflowDecision,
  comment?: string,
): Promise<WorkflowInstance> {
  return apiFetch<Envelope<WorkflowInstance>>(`/api/v1/workflow-instances/${instanceId}/actions`, {
    method: "POST",
    body: { action, comment },
  }).then((res) => res.data);
}
