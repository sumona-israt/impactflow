"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useRef, useState } from "react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import {
  attachmentDownloadUrl,
  getExpense,
  submitExpense,
  uploadExpenseAttachment,
} from "@/lib/api/endpoints/expenses";
import { recordWorkflowAction } from "@/lib/api/endpoints/workflow";
import { ApiError } from "@/lib/api/errors";
import { formatCurrency, formatDate, statusLabel } from "@/lib/format";

export function ExpenseDetail({
  expenseId,
  currentUserId,
  currentUserRoles,
}: {
  expenseId: string;
  currentUserId: number;
  currentUserRoles: string[];
}) {
  const queryClient = useQueryClient();
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [comment, setComment] = useState("");

  const { data: expense, isLoading, isError } = useQuery({
    queryKey: ["expenses", expenseId],
    queryFn: () => getExpense(expenseId),
  });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ["expenses", expenseId] });

  const submitMutation = useMutation({
    mutationFn: () => submitExpense(expenseId),
    onSuccess: () => {
      toast.success("Expense submitted for review.");
      invalidate();
    },
    onError: (error) => toast.error(error instanceof ApiError ? error.message : "Failed to submit expense."),
  });

  const uploadMutation = useMutation({
    mutationFn: (file: File) => uploadExpenseAttachment(expenseId, file),
    onSuccess: () => {
      toast.success("Receipt uploaded.");
      invalidate();
      if (fileInputRef.current) fileInputRef.current.value = "";
    },
    onError: (error) => toast.error(error instanceof ApiError ? error.message : "Failed to upload receipt."),
  });

  const actionMutation = useMutation({
    mutationFn: ({ action }: { action: "approve" | "reject" | "return" }) => {
      const instanceId = expense!.workflow_instance!.id;
      return recordWorkflowAction(instanceId, action, comment || undefined);
    },
    onSuccess: () => {
      toast.success("Decision recorded.");
      setComment("");
      invalidate();
    },
    onError: (error) => toast.error(error instanceof ApiError ? error.message : "Failed to record decision."),
  });

  if (isLoading) return <Skeleton className="h-64 w-full" />;
  if (isError || !expense) return <p className="text-sm text-destructive">Failed to load this expense.</p>;

  const isOwnDraft = expense.status === "draft" && expense.submitter.id === currentUserId;
  const currentStep = expense.workflow_instance?.status === "in_progress" ? expense.workflow_instance.current_step : null;
  const canAct = currentStep !== null && currentUserRoles.includes(currentStep.role_required);

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between">
        <div>
          <h1 className="text-lg font-semibold tracking-tight">{expense.program.name} — Expense</h1>
          <p className="text-sm text-muted-foreground">Submitted by {expense.submitter.name}</p>
        </div>
        <Badge variant="secondary">{statusLabel(expense.status)}</Badge>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-sm font-medium text-muted-foreground">Amount</CardTitle></CardHeader>
          <CardContent><p className="text-xl font-semibold">{formatCurrency(expense.amount)}</p></CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-sm font-medium text-muted-foreground">Category</CardTitle></CardHeader>
          <CardContent><p className="text-xl font-semibold">{expense.category?.name ?? "—"}</p></CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-sm font-medium text-muted-foreground">Date</CardTitle></CardHeader>
          <CardContent><p className="text-xl font-semibold">{formatDate(expense.expense_date)}</p></CardContent>
        </Card>
      </div>

      {expense.description && (
        <Card>
          <CardHeader><CardTitle className="text-sm font-medium">Description</CardTitle></CardHeader>
          <CardContent className="text-sm text-muted-foreground">{expense.description}</CardContent>
        </Card>
      )}

      <Card>
        <CardHeader><CardTitle className="text-sm font-medium">Receipts</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          {expense.attachments.length === 0 && (
            <p className="text-sm text-muted-foreground">No receipts uploaded yet.</p>
          )}
          <ul className="space-y-1">
            {expense.attachments.map((attachment) => (
              <li key={attachment.id}>
                <a
                  href={attachmentDownloadUrl(expense.id, attachment.id)}
                  className="text-sm text-primary hover:underline"
                >
                  {attachment.original_name}
                </a>
                <span className="ml-2 text-xs text-muted-foreground">
                  {(attachment.size / 1024).toFixed(0)} KB
                </span>
              </li>
            ))}
          </ul>
          {isOwnDraft && (
            <div className="flex items-center gap-2 pt-2">
              <Input
                ref={fileInputRef}
                type="file"
                accept=".pdf,.jpg,.jpeg,.png"
                className="max-w-xs"
                onChange={(e) => {
                  const file = e.target.files?.[0];
                  if (file) uploadMutation.mutate(file);
                }}
              />
              {uploadMutation.isPending && <span className="text-xs text-muted-foreground">Uploading…</span>}
            </div>
          )}
        </CardContent>
      </Card>

      {isOwnDraft && (
        <Button onClick={() => submitMutation.mutate()} disabled={submitMutation.isPending}>
          {submitMutation.isPending ? "Submitting…" : "Submit for review"}
        </Button>
      )}

      {expense.workflow_instance && (
        <Card>
          <CardHeader><CardTitle className="text-sm font-medium">Approval history</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            {expense.workflow_instance.actions.length === 0 && (
              <p className="text-sm text-muted-foreground">No decisions recorded yet.</p>
            )}
            <ul className="space-y-2">
              {expense.workflow_instance.actions.map((action) => (
                <li key={action.id} className="text-sm">
                  <span className="font-medium">{action.actor.name}</span>{" "}
                  <span className="text-muted-foreground">{action.action}d at {statusLabel(action.step)}</span>
                  {action.comment && <p className="text-muted-foreground italic">&ldquo;{action.comment}&rdquo;</p>}
                  <p className="text-xs text-muted-foreground">{formatDate(action.created_at)}</p>
                </li>
              ))}
            </ul>

            {canAct && (
              <div className="space-y-2 border-t pt-3">
                <Label htmlFor="review-comment">Comment (optional)</Label>
                <Input id="review-comment" value={comment} onChange={(e) => setComment(e.target.value)} />
                <div className="flex gap-2">
                  <Button size="sm" disabled={actionMutation.isPending} onClick={() => actionMutation.mutate({ action: "approve" })}>
                    Approve
                  </Button>
                  <Button size="sm" variant="outline" disabled={actionMutation.isPending} onClick={() => actionMutation.mutate({ action: "return" })}>
                    Return
                  </Button>
                  <Button size="sm" variant="destructive" disabled={actionMutation.isPending} onClick={() => actionMutation.mutate({ action: "reject" })}>
                    Reject
                  </Button>
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      )}
    </div>
  );
}
