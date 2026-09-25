export type ExpenseStatus = "draft" | "program_review" | "finance_review" | "approved" | "rejected";
export type AssetStatus = "available" | "assigned" | "maintenance" | "lost" | "retired";
export type WorkflowDecision = "approve" | "reject" | "return";

export interface ExpenseCategory {
  id: number;
  name: string;
}

export interface ExpenseAttachment {
  id: number;
  original_name: string;
  mime_type: string;
  size: number;
  created_at: string;
}

export interface WorkflowActionEntry {
  id: number;
  action: WorkflowDecision;
  comment: string | null;
  created_at: string;
  step: string;
  actor: { id: number; name: string };
}

export interface WorkflowInstance {
  id: number;
  status: "in_progress" | "approved" | "rejected" | "returned";
  current_step: { name: string; role_required: string } | null;
  actions: WorkflowActionEntry[];
}

export interface Expense {
  id: string;
  program: { id: string; name: string };
  category: { id: number; name: string } | null;
  submitter: { id: number; name: string };
  amount: string;
  currency: string;
  expense_date: string;
  description: string | null;
  status: ExpenseStatus;
  attachments: ExpenseAttachment[];
  workflow_instance: WorkflowInstance | null;
  created_at: string;
}

export interface Asset {
  id: string;
  name: string;
  category: string | null;
  serial_number: string | null;
  purchase_date: string | null;
  purchase_value: string | null;
  location: string | null;
  condition: string | null;
  status: AssetStatus;
  assigned_to: { id: number; name: string; assigned_at: string } | null;
}

export interface ApprovalWorkflowDefinition {
  id: number;
  name: string;
  entity_type: string;
  steps: { sequence: number; name: string; role_required: string }[];
}
