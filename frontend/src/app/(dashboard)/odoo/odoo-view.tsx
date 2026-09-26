"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";
import { Switch } from "@/components/ui/switch";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { PaginationControls } from "@/components/shared/pagination-controls";
import {
  getOdooConfig,
  getOdooStatus,
  listOdooSyncLogs,
  retryOdooSync,
  updateOdooConfig,
} from "@/lib/api/endpoints/odoo";
import { ApiError } from "@/lib/api/errors";
import { formatDate, statusLabel } from "@/lib/format";

export function OdooView({ canRetry, canManageConfig }: { canRetry: boolean; canManageConfig: boolean }) {
  const [statusFilter, setStatusFilter] = useState<string>("all");
  const [page, setPage] = useState(1);
  const queryClient = useQueryClient();

  const statusQuery = useQuery({ queryKey: ["odoo-status"], queryFn: getOdooStatus });
  const configQuery = useQuery({ queryKey: ["odoo-config"], queryFn: getOdooConfig, enabled: canManageConfig });
  const logsQuery = useQuery({
    queryKey: ["odoo-sync-logs", statusFilter, page],
    queryFn: () => listOdooSyncLogs(page, statusFilter === "all" ? undefined : statusFilter),
  });

  const retryMutation = useMutation({
    mutationFn: ({ entity, id }: { entity: string; id: string }) => retryOdooSync(entity, id),
    onSuccess: () => {
      toast.success("Sync retry queued.");
      queryClient.invalidateQueries({ queryKey: ["odoo-sync-logs"] });
      queryClient.invalidateQueries({ queryKey: ["odoo-status"] });
    },
    onError: (error) => toast.error(error instanceof ApiError ? error.message : "Failed to queue retry."),
  });

  const toggleActiveMutation = useMutation({
    mutationFn: updateOdooConfig,
    onSuccess: (config) => {
      toast.success(config.is_active ? "Odoo sync resumed." : "Odoo sync paused.");
      queryClient.invalidateQueries({ queryKey: ["odoo-config"] });
      queryClient.invalidateQueries({ queryKey: ["odoo-status"] });
    },
    onError: (error) => toast.error(error instanceof ApiError ? error.message : "Failed to update config."),
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <div>
          <h1 className="text-lg font-semibold tracking-tight">Odoo Integration</h1>
          <p className="text-sm text-muted-foreground">Sync status, history, and retry for Odoo-integrated entities.</p>
        </div>
        {statusQuery.data?.mode === "mock" && <Badge variant="outline">Mock Odoo Environment</Badge>}
        {statusQuery.data && !statusQuery.data.is_active && <Badge variant="destructive">Sync paused</Badge>}
      </div>

      {statusQuery.isLoading ? (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {Array.from({ length: 4 }).map((_, i) => (
            <Skeleton key={i} className="h-24 w-full" />
          ))}
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {Object.entries(statusQuery.data?.entities ?? {}).map(([slug, counts]) => (
            <Card key={slug}>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground capitalize">{slug}</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-2xl font-semibold">
                  {counts.synced} <span className="text-sm font-normal text-muted-foreground">synced</span>
                </p>
                <p className="mt-1 text-xs text-muted-foreground">
                  {counts.failed} failed · last success {formatDate(counts.last_success_at)}
                </p>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {canManageConfig && (
        <Card className="max-w-md">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium">Connection</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <p className="text-sm text-muted-foreground">
              Mode: <span className="font-medium text-foreground">{configQuery.data?.mode ?? "—"}</span>
            </p>
            <div className="flex items-center gap-3">
              <Switch
                checked={configQuery.data?.is_active ?? true}
                disabled={configQuery.isLoading || toggleActiveMutation.isPending}
                onCheckedChange={(checked) => toggleActiveMutation.mutate({ is_active: checked })}
              />
              <Label>Sync active</Label>
            </div>
          </CardContent>
        </Card>
      )}

      <div className="space-y-3">
        <div className="flex items-center justify-between">
          <h2 className="text-sm font-medium">Sync log</h2>
          <Select
            value={statusFilter}
            onValueChange={(v) => {
              setStatusFilter(v ?? "all");
              setPage(1);
            }}
          >
            <SelectTrigger className="w-40">
              <SelectValue placeholder="All statuses" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All statuses</SelectItem>
              <SelectItem value="success">Success</SelectItem>
              <SelectItem value="failed">Failed</SelectItem>
              <SelectItem value="skipped">Skipped</SelectItem>
              <SelectItem value="pending">Pending</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div className="rounded-lg border bg-card">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Entity</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Odoo ID</TableHead>
                <TableHead>Retries</TableHead>
                <TableHead>Last attempt</TableHead>
                {canRetry && <TableHead className="text-right">Actions</TableHead>}
              </TableRow>
            </TableHeader>
            <TableBody>
              {logsQuery.isLoading &&
                Array.from({ length: 4 }).map((_, i) => (
                  <TableRow key={i}>
                    <TableCell colSpan={6}>
                      <Skeleton className="h-6 w-full" />
                    </TableCell>
                  </TableRow>
                ))}
              {logsQuery.data?.data.length === 0 && (
                <TableRow>
                  <TableCell colSpan={6} className="py-6 text-center text-sm text-muted-foreground">
                    No sync activity yet.
                  </TableCell>
                </TableRow>
              )}
              {logsQuery.data?.data.map((log) => (
                <TableRow key={log.id}>
                  <TableCell className="font-medium">{log.entity_type}</TableCell>
                  <TableCell>
                    <Badge variant={log.status === "failed" ? "destructive" : "secondary"}>
                      {statusLabel(log.status)}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-muted-foreground">{log.odoo_id ?? "—"}</TableCell>
                  <TableCell className="text-muted-foreground">{log.retry_count}</TableCell>
                  <TableCell className="text-muted-foreground">{formatDate(log.response_time ?? log.request_time)}</TableCell>
                  {canRetry && (
                    <TableCell className="text-right">
                      {log.status === "failed" && (
                        <Button
                          variant="outline"
                          size="sm"
                          disabled={retryMutation.isPending}
                          onClick={() =>
                            retryMutation.mutate({ entity: log.entity_type.toLowerCase(), id: log.local_id })
                          }
                        >
                          Retry
                        </Button>
                      )}
                    </TableCell>
                  )}
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
        {logsQuery.data && <PaginationControls meta={logsQuery.data.meta} onPageChange={setPage} />}
      </div>
    </div>
  );
}
