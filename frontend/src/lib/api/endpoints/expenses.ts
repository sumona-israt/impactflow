import { apiFetch } from "@/lib/api/client";
import type { Expense, ExpenseAttachment, ExpenseCategory, ExpenseStatus } from "@/types/finance";
import type { PageMeta } from "@/types/rbac";

interface ListEnvelope<T> {
  data: T[];
  meta: PageMeta;
}

interface Envelope<T> {
  data: T;
}

export function listExpenseCategories(): Promise<ExpenseCategory[]> {
  return apiFetch<Envelope<ExpenseCategory[]>>("/api/v1/expense-categories").then((res) => res.data);
}

export function createExpenseCategory(name: string): Promise<ExpenseCategory> {
  return apiFetch<Envelope<ExpenseCategory>>("/api/v1/expense-categories", {
    method: "POST",
    body: { name },
  }).then((res) => res.data);
}

export interface ListExpensesParams {
  page?: number;
  status?: ExpenseStatus;
  programId?: string;
}

function buildQuery(params: ListExpensesParams): string {
  const search = new URLSearchParams();
  if (params.page) search.set("page", String(params.page));
  if (params.status) search.set("filter[status]", params.status);
  if (params.programId) search.set("filter[program_id]", params.programId);
  const qs = search.toString();
  return qs ? `?${qs}` : "";
}

export function listExpenses(params: ListExpensesParams = {}): Promise<ListEnvelope<Expense>> {
  return apiFetch<ListEnvelope<Expense>>(`/api/v1/expenses${buildQuery(params)}`);
}

export function getExpense(id: string): Promise<Expense> {
  return apiFetch<Envelope<Expense>>(`/api/v1/expenses/${id}`).then((res) => res.data);
}

export interface CreateExpensePayload {
  program_id: string;
  category_id?: number;
  amount: number;
  currency?: string;
  expense_date: string;
  description?: string;
}

export function createExpense(payload: CreateExpensePayload): Promise<Expense> {
  return apiFetch<Envelope<Expense>>("/api/v1/expenses", { method: "POST", body: payload }).then((res) => res.data);
}

export function updateExpense(id: string, payload: Partial<CreateExpensePayload>): Promise<Expense> {
  return apiFetch<Envelope<Expense>>(`/api/v1/expenses/${id}`, { method: "PUT", body: payload }).then(
    (res) => res.data,
  );
}

export function submitExpense(id: string): Promise<Expense> {
  return apiFetch<Envelope<Expense>>(`/api/v1/expenses/${id}/submit`, { method: "POST" }).then((res) => res.data);
}

export function uploadExpenseAttachment(expenseId: string, file: File): Promise<ExpenseAttachment> {
  const formData = new FormData();
  formData.append("file", file);

  return apiFetch<Envelope<ExpenseAttachment>>(`/api/v1/expenses/${expenseId}/attachments`, {
    method: "POST",
    body: formData,
  }).then((res) => res.data);
}

export function attachmentDownloadUrl(expenseId: string, attachmentId: number): string {
  const base = process.env.NEXT_PUBLIC_API_URL ?? "";
  return `${base}/api/v1/expenses/${expenseId}/attachments/${attachmentId}/download`;
}
