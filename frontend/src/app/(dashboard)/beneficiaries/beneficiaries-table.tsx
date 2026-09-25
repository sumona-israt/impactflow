"use client";

import { useQuery } from "@tanstack/react-query";
import Link from "next/link";
import { useEffect, useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
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
import { listBeneficiaries } from "@/lib/api/endpoints/beneficiaries";
import { formatDate, statusLabel } from "@/lib/format";
import { CreateBeneficiaryDialog } from "./create-beneficiary-dialog";

export function BeneficiariesTable({ canCreate }: { canCreate: boolean }) {
  const [page, setPage] = useState(1);
  const [q, setQ] = useState("");
  const [debouncedQ, setDebouncedQ] = useState("");

  useEffect(() => {
    const timeout = setTimeout(() => {
      setDebouncedQ(q);
      setPage(1);
    }, 300);
    return () => clearTimeout(timeout);
  }, [q]);

  const { data, isLoading, isError } = useQuery({
    queryKey: ["beneficiaries", { page, q: debouncedQ }],
    queryFn: () => listBeneficiaries({ page, q: debouncedQ || undefined }),
  });

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-lg font-semibold tracking-tight">Beneficiaries</h1>
          <p className="text-sm text-muted-foreground">
            Sensitive details (phone, date of birth, address) are only shown on a beneficiary&apos;s own page.
          </p>
        </div>
        {canCreate && <CreateBeneficiaryDialog />}
      </div>

      <Input placeholder="Search by name…" value={q} onChange={(e) => setQ(e.target.value)} className="w-56" />

      <div className="rounded-lg border bg-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>District</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Registered</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading &&
              Array.from({ length: 5 }).map((_, i) => (
                <TableRow key={i}><TableCell colSpan={4}><Skeleton className="h-6 w-full" /></TableCell></TableRow>
              ))}
            {isError && (
              <TableRow><TableCell colSpan={4} className="py-8 text-center text-sm text-destructive">Failed to load beneficiaries.</TableCell></TableRow>
            )}
            {!isLoading && !isError && data?.data.length === 0 && (
              <TableRow><TableCell colSpan={4} className="py-8 text-center text-sm text-muted-foreground">No beneficiaries match this search.</TableCell></TableRow>
            )}
            {data?.data.map((beneficiary) => (
              <TableRow key={beneficiary.id}>
                <TableCell className="font-medium">
                  <Link href={`/beneficiaries/${beneficiary.id}`} className="hover:underline">{beneficiary.full_name}</Link>
                </TableCell>
                <TableCell className="text-muted-foreground">{beneficiary.district ?? "—"}</TableCell>
                <TableCell><Badge variant="secondary">{statusLabel(beneficiary.status)}</Badge></TableCell>
                <TableCell className="text-muted-foreground">{formatDate(beneficiary.registration_date)}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>

      {data && <PaginationControls meta={data.meta} onPageChange={setPage} />}
    </div>
  );
}
