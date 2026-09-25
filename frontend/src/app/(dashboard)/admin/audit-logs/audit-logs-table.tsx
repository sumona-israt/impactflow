"use client";

import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { PaginationControls } from "@/components/shared/pagination-controls";
import { listAuditLogs } from "@/lib/api/endpoints/audit-logs";
import { AUDIT_ACTIONS, auditActionLabel } from "@/lib/rbac/labels";

function truncatedJson(value: Record<string, unknown> | null): string {
  if (!value || Object.keys(value).length === 0) return "—";
  const json = JSON.stringify(value);
  return json.length > 60 ? `${json.slice(0, 60)}…` : json;
}

export function AuditLogsTable() {
  const [page, setPage] = useState(1);
  const [action, setAction] = useState("all");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");

  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin", "audit-logs", { page, action, dateFrom, dateTo }],
    queryFn: () =>
      listAuditLogs({
        page,
        action: action === "all" ? undefined : action,
        dateFrom: dateFrom || undefined,
        dateTo: dateTo || undefined,
      }),
  });

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center gap-2">
        <Select value={action} onValueChange={(v) => { setAction(v ?? "all"); setPage(1); }}>
          <SelectTrigger className="w-56"><SelectValue placeholder="All actions" /></SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All actions</SelectItem>
            {AUDIT_ACTIONS.map((a) => (
              <SelectItem key={a} value={a}>{auditActionLabel(a)}</SelectItem>
            ))}
          </SelectContent>
        </Select>
        <Input
          type="date"
          value={dateFrom}
          onChange={(e) => { setDateFrom(e.target.value); setPage(1); }}
          className="w-40"
          aria-label="From date"
        />
        <Input
          type="date"
          value={dateTo}
          onChange={(e) => { setDateTo(e.target.value); setPage(1); }}
          className="w-40"
          aria-label="To date"
        />
      </div>

      <div className="rounded-lg border bg-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>When</TableHead>
              <TableHead>Actor</TableHead>
              <TableHead>Action</TableHead>
              <TableHead>Entity</TableHead>
              <TableHead>Before</TableHead>
              <TableHead>After</TableHead>
              <TableHead>IP</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading &&
              Array.from({ length: 5 }).map((_, i) => (
                <TableRow key={i}>
                  <TableCell colSpan={7}><Skeleton className="h-6 w-full" /></TableCell>
                </TableRow>
              ))}

            {isError && (
              <TableRow>
                <TableCell colSpan={7} className="py-8 text-center text-sm text-destructive">
                  Failed to load the audit trail.
                </TableCell>
              </TableRow>
            )}

            {!isLoading && !isError && data?.data.length === 0 && (
              <TableRow>
                <TableCell colSpan={7} className="py-8 text-center text-sm text-muted-foreground">
                  No audit entries match these filters.
                </TableCell>
              </TableRow>
            )}

            {data?.data.map((log) => (
              <TableRow key={log.id}>
                <TableCell className="whitespace-nowrap text-muted-foreground">
                  {new Date(log.created_at).toLocaleString()}
                </TableCell>
                <TableCell>{log.user ? log.user.name : "System"}</TableCell>
                <TableCell>
                  <Badge variant="secondary">{auditActionLabel(log.action)}</Badge>
                </TableCell>
                <TableCell className="text-muted-foreground">
                  {log.entity_type}
                  {log.entity_id ? ` #${log.entity_id}` : ""}
                </TableCell>
                <TableCell className="max-w-48 truncate font-mono text-xs text-muted-foreground">
                  {truncatedJson(log.old_values)}
                </TableCell>
                <TableCell className="max-w-48 truncate font-mono text-xs text-muted-foreground">
                  {truncatedJson(log.new_values)}
                </TableCell>
                <TableCell className="text-muted-foreground">{log.ip_address ?? "—"}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>

      {data && <PaginationControls meta={data.meta} onPageChange={setPage} />}
    </div>
  );
}
