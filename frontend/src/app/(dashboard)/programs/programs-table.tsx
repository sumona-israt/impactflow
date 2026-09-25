"use client";

import { useQuery } from "@tanstack/react-query";
import Link from "next/link";
import { useEffect, useState } from "react";
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
import { listPrograms } from "@/lib/api/endpoints/programs";
import { formatCurrency, formatDate, statusLabel } from "@/lib/format";
import type { ProgramStatus } from "@/types/programs";
import { CreateProgramDialog } from "./create-program-dialog";

const STATUSES: ProgramStatus[] = [
  "draft", "pending_approval", "approved", "active", "paused", "completed", "archived",
];

export function ProgramsTable({ canCreate }: { canCreate: boolean }) {
  const [page, setPage] = useState(1);
  const [q, setQ] = useState("");
  const [debouncedQ, setDebouncedQ] = useState("");
  const [status, setStatus] = useState("all");

  useEffect(() => {
    const timeout = setTimeout(() => {
      setDebouncedQ(q);
      setPage(1);
    }, 300);
    return () => clearTimeout(timeout);
  }, [q]);

  const { data, isLoading, isError } = useQuery({
    queryKey: ["programs", { page, q: debouncedQ, status }],
    queryFn: () =>
      listPrograms({
        page,
        q: debouncedQ || undefined,
        status: status === "all" ? undefined : (status as ProgramStatus),
      }),
  });

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-lg font-semibold tracking-tight">Programs</h1>
          <p className="text-sm text-muted-foreground">NGO programs across all branches and categories.</p>
        </div>
        {canCreate && <CreateProgramDialog />}
      </div>

      <div className="flex flex-wrap items-center gap-2">
        <Input placeholder="Search programs…" value={q} onChange={(e) => setQ(e.target.value)} className="w-56" />
        <Select value={status} onValueChange={(v) => { setStatus(v ?? "all"); setPage(1); }}>
          <SelectTrigger className="w-44"><SelectValue placeholder="All statuses" /></SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All statuses</SelectItem>
            {STATUSES.map((s) => (
              <SelectItem key={s} value={s}>{statusLabel(s)}</SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      <div className="rounded-lg border bg-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>Category</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Beneficiaries</TableHead>
              <TableHead>Budget</TableHead>
              <TableHead>Start date</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading &&
              Array.from({ length: 5 }).map((_, i) => (
                <TableRow key={i}><TableCell colSpan={6}><Skeleton className="h-6 w-full" /></TableCell></TableRow>
              ))}

            {isError && (
              <TableRow><TableCell colSpan={6} className="py-8 text-center text-sm text-destructive">Failed to load programs.</TableCell></TableRow>
            )}

            {!isLoading && !isError && data?.data.length === 0 && (
              <TableRow><TableCell colSpan={6} className="py-8 text-center text-sm text-muted-foreground">No programs match these filters.</TableCell></TableRow>
            )}

            {data?.data.map((program) => (
              <TableRow key={program.id}>
                <TableCell className="font-medium">
                  <Link href={`/programs/${program.id}`} className="hover:underline">{program.name}</Link>
                </TableCell>
                <TableCell className="text-muted-foreground">{program.category?.name ?? "—"}</TableCell>
                <TableCell><Badge variant="secondary">{statusLabel(program.status)}</Badge></TableCell>
                <TableCell className="text-muted-foreground">
                  {program.actual_beneficiaries}{program.target_beneficiaries ? ` / ${program.target_beneficiaries}` : ""}
                </TableCell>
                <TableCell className="text-muted-foreground">{formatCurrency(program.budget)}</TableCell>
                <TableCell className="text-muted-foreground">{formatDate(program.start_date)}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>

      {data && <PaginationControls meta={data.meta} onPageChange={setPage} />}
    </div>
  );
}
