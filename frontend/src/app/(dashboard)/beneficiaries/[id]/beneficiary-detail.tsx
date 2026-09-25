"use client";

import { useQuery } from "@tanstack/react-query";
import Link from "next/link";
import { useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { getBeneficiary, listBeneficiaryEnrollments } from "@/lib/api/endpoints/beneficiaries";
import { formatDate, statusLabel } from "@/lib/format";
import { EditBeneficiaryDialog } from "./edit-beneficiary-dialog";

export function BeneficiaryDetail({ beneficiaryId, canUpdate }: { beneficiaryId: string; canUpdate: boolean }) {
  const [editing, setEditing] = useState(false);

  const beneficiaryQuery = useQuery({
    queryKey: ["beneficiaries", beneficiaryId],
    queryFn: () => getBeneficiary(beneficiaryId),
  });
  const enrollmentsQuery = useQuery({
    queryKey: ["beneficiary-enrollments", beneficiaryId],
    queryFn: () => listBeneficiaryEnrollments(beneficiaryId),
  });

  if (beneficiaryQuery.isLoading) return <Skeleton className="h-64 w-full" />;
  if (beneficiaryQuery.isError || !beneficiaryQuery.data) {
    return <p className="text-sm text-destructive">Failed to load this beneficiary.</p>;
  }

  const beneficiary = beneficiaryQuery.data;

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between">
        <div>
          <h1 className="text-lg font-semibold tracking-tight">{beneficiary.full_name}</h1>
          <p className="text-sm text-muted-foreground">Registered {formatDate(beneficiary.registration_date)}</p>
        </div>
        <div className="flex items-center gap-2">
          <Badge variant="secondary">{statusLabel(beneficiary.status)}</Badge>
          {canUpdate && (
            <Button variant="outline" size="sm" onClick={() => setEditing(true)}>Edit</Button>
          )}
        </div>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <Card>
          <CardHeader><CardTitle className="text-sm font-medium">Contact</CardTitle></CardHeader>
          <CardContent className="space-y-1 text-sm text-muted-foreground">
            <p>Phone: {beneficiary.phone ?? "—"}</p>
            <p>Email: {beneficiary.email ?? "—"}</p>
            <p>Address: {beneficiary.address ?? "—"}</p>
            <p>District: {beneficiary.district ?? "—"}{beneficiary.upazila ? `, ${beneficiary.upazila}` : ""}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle className="text-sm font-medium">Personal</CardTitle></CardHeader>
          <CardContent className="space-y-1 text-sm text-muted-foreground">
            <p>Date of birth: {formatDate(beneficiary.date_of_birth)}</p>
            <p>Gender: {beneficiary.gender ?? "—"}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle className="text-sm font-medium">Emergency contact</CardTitle></CardHeader>
          <CardContent className="space-y-1 text-sm text-muted-foreground">
            <p>{beneficiary.emergency_contact_name ?? "—"}</p>
            <p>{beneficiary.emergency_contact_phone ?? "—"}</p>
          </CardContent>
        </Card>
      </div>

      {beneficiary.notes && (
        <Card>
          <CardHeader><CardTitle className="text-sm font-medium">Notes</CardTitle></CardHeader>
          <CardContent className="text-sm text-muted-foreground">{beneficiary.notes}</CardContent>
        </Card>
      )}

      <div>
        <h2 className="mb-2 text-sm font-semibold">Program enrollments</h2>
        {enrollmentsQuery.data?.length === 0 && (
          <p className="text-sm text-muted-foreground">Not enrolled in any programs yet.</p>
        )}
        <div className="flex flex-col gap-2">
          {enrollmentsQuery.data?.map((enrollment) => (
            <Link
              key={enrollment.id}
              href={`/programs/${enrollment.program?.id}`}
              className="flex items-center justify-between rounded-md border px-3 py-2 text-sm hover:bg-muted"
            >
              <span>{enrollment.program?.name}</span>
              <Badge variant={enrollment.status === "withdrawn" ? "outline" : "default"}>{statusLabel(enrollment.status)}</Badge>
            </Link>
          ))}
        </div>
      </div>

      {editing && (
        <EditBeneficiaryDialog beneficiary={beneficiary} open={editing} onOpenChange={setEditing} />
      )}
    </div>
  );
}
