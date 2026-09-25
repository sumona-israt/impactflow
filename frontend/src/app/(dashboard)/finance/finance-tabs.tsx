"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import Link from "next/link";
import { useState } from "react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { PaginationControls } from "@/components/shared/pagination-controls";
import { listAssets, returnAsset } from "@/lib/api/endpoints/assets";
import { listExpenses } from "@/lib/api/endpoints/expenses";
import { ApiError } from "@/lib/api/errors";
import { formatCurrency, formatDate, statusLabel } from "@/lib/format";
import type { Asset } from "@/types/finance";
import { AssignAssetDialog } from "./assign-asset-dialog";
import { CreateAssetDialog } from "./create-asset-dialog";
import { CreateExpenseDialog } from "./create-expense-dialog";

export function FinanceTabs({
  canViewExpenses,
  canCreateExpense,
  canViewAssets,
  canCreateAsset,
  canAssignAsset,
}: {
  canViewExpenses: boolean;
  canCreateExpense: boolean;
  canViewAssets: boolean;
  canCreateAsset: boolean;
  canAssignAsset: boolean;
}) {
  const [expensePage, setExpensePage] = useState(1);
  const [assetPage, setAssetPage] = useState(1);
  const [assigning, setAssigning] = useState<Asset | null>(null);
  const queryClient = useQueryClient();

  const expensesQuery = useQuery({
    queryKey: ["expenses", expensePage],
    queryFn: () => listExpenses({ page: expensePage }),
    enabled: canViewExpenses,
  });
  const assetsQuery = useQuery({
    queryKey: ["assets", assetPage],
    queryFn: () => listAssets({ page: assetPage }),
    enabled: canViewAssets,
  });

  const returnMutation = useMutation({
    mutationFn: (assetId: string) => returnAsset(assetId),
    onSuccess: () => {
      toast.success("Asset returned.");
      queryClient.invalidateQueries({ queryKey: ["assets"] });
    },
    onError: (error) => toast.error(error instanceof ApiError ? error.message : "Failed to return asset."),
  });

  const defaultTab = canViewExpenses ? "expenses" : "assets";

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-lg font-semibold tracking-tight">Finance &amp; Assets</h1>
        <p className="text-sm text-muted-foreground">Program expenses, approvals, and organizational assets.</p>
      </div>

      <Tabs defaultValue={defaultTab}>
        <TabsList>
          {canViewExpenses && <TabsTrigger value="expenses">Expenses</TabsTrigger>}
          {canViewAssets && <TabsTrigger value="assets">Assets</TabsTrigger>}
        </TabsList>

        {canViewExpenses && (
          <TabsContent value="expenses" className="space-y-3 pt-4">
            {canCreateExpense && <CreateExpenseDialog />}
            <div className="rounded-lg border bg-card">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Program</TableHead>
                    <TableHead>Category</TableHead>
                    <TableHead>Amount</TableHead>
                    <TableHead>Date</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Submitted by</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {expensesQuery.isLoading &&
                    Array.from({ length: 4 }).map((_, i) => (
                      <TableRow key={i}><TableCell colSpan={6}><Skeleton className="h-6 w-full" /></TableCell></TableRow>
                    ))}
                  {expensesQuery.data?.data.length === 0 && (
                    <TableRow><TableCell colSpan={6} className="py-6 text-center text-sm text-muted-foreground">No expenses yet.</TableCell></TableRow>
                  )}
                  {expensesQuery.data?.data.map((expense) => (
                    <TableRow key={expense.id}>
                      <TableCell className="font-medium">
                        <Link href={`/finance/expenses/${expense.id}`} className="hover:underline">
                          {expense.program.name}
                        </Link>
                      </TableCell>
                      <TableCell className="text-muted-foreground">{expense.category?.name ?? "—"}</TableCell>
                      <TableCell className="text-muted-foreground">{formatCurrency(expense.amount)}</TableCell>
                      <TableCell className="text-muted-foreground">{formatDate(expense.expense_date)}</TableCell>
                      <TableCell><Badge variant="secondary">{statusLabel(expense.status)}</Badge></TableCell>
                      <TableCell className="text-muted-foreground">{expense.submitter.name}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
            {expensesQuery.data && <PaginationControls meta={expensesQuery.data.meta} onPageChange={setExpensePage} />}
          </TabsContent>
        )}

        {canViewAssets && (
          <TabsContent value="assets" className="space-y-3 pt-4">
            {canCreateAsset && <CreateAssetDialog />}
            <div className="rounded-lg border bg-card">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Category</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Assigned to</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {assetsQuery.isLoading &&
                    Array.from({ length: 4 }).map((_, i) => (
                      <TableRow key={i}><TableCell colSpan={5}><Skeleton className="h-6 w-full" /></TableCell></TableRow>
                    ))}
                  {assetsQuery.data?.data.length === 0 && (
                    <TableRow><TableCell colSpan={5} className="py-6 text-center text-sm text-muted-foreground">No assets yet.</TableCell></TableRow>
                  )}
                  {assetsQuery.data?.data.map((asset) => (
                    <TableRow key={asset.id}>
                      <TableCell className="font-medium">{asset.name}</TableCell>
                      <TableCell className="text-muted-foreground">{asset.category ?? "—"}</TableCell>
                      <TableCell><Badge variant="secondary">{statusLabel(asset.status)}</Badge></TableCell>
                      <TableCell className="text-muted-foreground">{asset.assigned_to?.name ?? "—"}</TableCell>
                      <TableCell className="text-right">
                        {canAssignAsset && asset.status === "available" && (
                          <Button variant="outline" size="sm" onClick={() => setAssigning(asset)}>Assign</Button>
                        )}
                        {canAssignAsset && asset.status === "assigned" && (
                          <Button
                            variant="outline"
                            size="sm"
                            disabled={returnMutation.isPending}
                            onClick={() => returnMutation.mutate(asset.id)}
                          >
                            Return
                          </Button>
                        )}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
            {assetsQuery.data && <PaginationControls meta={assetsQuery.data.meta} onPageChange={setAssetPage} />}
          </TabsContent>
        )}
      </Tabs>

      {assigning && (
        <AssignAssetDialog asset={assigning} open={Boolean(assigning)} onOpenChange={(open) => !open && setAssigning(null)} />
      )}
    </div>
  );
}
