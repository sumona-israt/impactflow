"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { getProgram, listProgramActivities, listProgramBeneficiaries, unenrollBeneficiary, updateProgramStatus } from "@/lib/api/endpoints/programs";
import { ApiError } from "@/lib/api/errors";
import { formatCurrency, formatDate, statusLabel } from "@/lib/format";
import type { Activity } from "@/types/activities";
import type { ProgramStatus } from "@/types/programs";
import { AttendanceDialog } from "./attendance-dialog";
import { CreateActivityDialog } from "./create-activity-dialog";
import { EnrollBeneficiaryDialog } from "./enroll-beneficiary-dialog";

const NEXT_STATUSES: Record<ProgramStatus, ProgramStatus[]> = {
  draft: ["pending_approval", "archived"],
  pending_approval: ["approved", "draft"],
  approved: ["active", "archived"],
  active: ["paused", "completed", "archived"],
  paused: ["active", "archived"],
  completed: ["archived"],
  archived: [],
};

export function ProgramDetail({
  programId,
  canUpdateStatus,
  canEnroll,
  canCreateActivity,
  canRecordAttendance,
}: {
  programId: string;
  canUpdate: boolean;
  canUpdateStatus: boolean;
  canEnroll: boolean;
  canCreateActivity: boolean;
  canRecordAttendance: boolean;
}) {
  const queryClient = useQueryClient();
  const [attendanceActivity, setAttendanceActivity] = useState<Activity | null>(null);

  const programQuery = useQuery({ queryKey: ["programs", programId], queryFn: () => getProgram(programId) });
  const beneficiariesQuery = useQuery({
    queryKey: ["program-beneficiaries", programId],
    queryFn: () => listProgramBeneficiaries(programId),
  });
  const activitiesQuery = useQuery({
    queryKey: ["program-activities", programId],
    queryFn: () => listProgramActivities(programId),
  });

  const statusMutation = useMutation({
    mutationFn: (status: ProgramStatus) => updateProgramStatus(programId, status),
    onSuccess: () => {
      toast.success("Program status updated.");
      queryClient.invalidateQueries({ queryKey: ["programs", programId] });
    },
    onError: (error) => toast.error(error instanceof ApiError ? error.message : "Failed to update status."),
  });

  const unenrollMutation = useMutation({
    mutationFn: (enrollmentId: number) => unenrollBeneficiary(programId, enrollmentId),
    onSuccess: () => {
      toast.success("Beneficiary unenrolled.");
      queryClient.invalidateQueries({ queryKey: ["program-beneficiaries", programId] });
      queryClient.invalidateQueries({ queryKey: ["programs", programId] });
    },
    onError: (error) => toast.error(error instanceof ApiError ? error.message : "Failed to unenroll."),
  });

  if (programQuery.isLoading) return <Skeleton className="h-64 w-full" />;
  if (programQuery.isError || !programQuery.data) {
    return <p className="text-sm text-destructive">Failed to load this program.</p>;
  }

  const program = programQuery.data;
  const nextStatuses = NEXT_STATUSES[program.status];

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between">
        <div>
          <h1 className="text-lg font-semibold tracking-tight">{program.name}</h1>
          <p className="text-sm text-muted-foreground">{program.description ?? "No description provided."}</p>
        </div>
        <div className="flex items-center gap-2">
          <Badge variant="secondary">{statusLabel(program.status)}</Badge>
          {canUpdateStatus && nextStatuses.length > 0 && (
            <Select onValueChange={(v) => v && statusMutation.mutate(v as ProgramStatus)}>
              <SelectTrigger className="w-40"><SelectValue placeholder="Change status" /></SelectTrigger>
              <SelectContent>
                {nextStatuses.map((s) => (
                  <SelectItem key={s} value={s}>{statusLabel(s)}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          )}
        </div>
      </div>

      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm font-medium text-muted-foreground">Beneficiaries</CardTitle></CardHeader>
          <CardContent><p className="text-xl font-semibold">{program.actual_beneficiaries}{program.target_beneficiaries ? ` / ${program.target_beneficiaries}` : ""}</p></CardContent>
        </Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm font-medium text-muted-foreground">Budget</CardTitle></CardHeader>
          <CardContent><p className="text-xl font-semibold">{formatCurrency(program.budget)}</p></CardContent>
        </Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm font-medium text-muted-foreground">Start date</CardTitle></CardHeader>
          <CardContent><p className="text-xl font-semibold">{formatDate(program.start_date)}</p></CardContent>
        </Card>
        <Card><CardHeader className="pb-2"><CardTitle className="text-sm font-medium text-muted-foreground">Branch</CardTitle></CardHeader>
          <CardContent><p className="text-xl font-semibold">{program.branch?.name ?? "—"}</p></CardContent>
        </Card>
      </div>

      <Tabs defaultValue="beneficiaries">
        <TabsList>
          <TabsTrigger value="beneficiaries">Beneficiaries</TabsTrigger>
          <TabsTrigger value="activities">Activities</TabsTrigger>
        </TabsList>

        <TabsContent value="beneficiaries" className="space-y-3 pt-4">
          {canEnroll && <EnrollBeneficiaryDialog programId={programId} />}
          <div className="rounded-lg border bg-card">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>District</TableHead>
                  <TableHead>Enrolled</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {beneficiariesQuery.data?.length === 0 && (
                  <TableRow><TableCell colSpan={5} className="py-6 text-center text-sm text-muted-foreground">No beneficiaries enrolled yet.</TableCell></TableRow>
                )}
                {beneficiariesQuery.data?.map((enrollment) => (
                  <TableRow key={enrollment.id}>
                    <TableCell className="font-medium">{enrollment.beneficiary?.full_name}</TableCell>
                    <TableCell className="text-muted-foreground">{enrollment.beneficiary?.district ?? "—"}</TableCell>
                    <TableCell className="text-muted-foreground">{formatDate(enrollment.enrolled_at)}</TableCell>
                    <TableCell><Badge variant={enrollment.status === "withdrawn" ? "outline" : "default"}>{statusLabel(enrollment.status)}</Badge></TableCell>
                    <TableCell className="text-right">
                      {canEnroll && enrollment.status === "enrolled" && (
                        <Button variant="outline" size="sm" onClick={() => unenrollMutation.mutate(enrollment.id)}>
                          Unenroll
                        </Button>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        </TabsContent>

        <TabsContent value="activities" className="space-y-3 pt-4">
          {canCreateActivity && <CreateActivityDialog programId={programId} />}
          <div className="rounded-lg border bg-card">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Title</TableHead>
                  <TableHead>Scheduled</TableHead>
                  <TableHead>Location</TableHead>
                  <TableHead>Attendance</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {activitiesQuery.data?.length === 0 && (
                  <TableRow><TableCell colSpan={5} className="py-6 text-center text-sm text-muted-foreground">No activities yet.</TableCell></TableRow>
                )}
                {activitiesQuery.data?.map((activity) => (
                  <TableRow key={activity.id}>
                    <TableCell className="font-medium">{activity.title}</TableCell>
                    <TableCell className="text-muted-foreground">{formatDate(activity.scheduled_at)}</TableCell>
                    <TableCell className="text-muted-foreground">{activity.location ?? "—"}</TableCell>
                    <TableCell className="text-muted-foreground">{activity.attendance_count ?? 0}</TableCell>
                    <TableCell className="text-right">
                      {canRecordAttendance && (
                        <Button variant="outline" size="sm" onClick={() => setAttendanceActivity(activity)}>
                          Record attendance
                        </Button>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        </TabsContent>
      </Tabs>

      {attendanceActivity && (
        <AttendanceDialog
          activity={attendanceActivity}
          programId={programId}
          open={Boolean(attendanceActivity)}
          onOpenChange={(open) => !open && setAttendanceActivity(null)}
        />
      )}
    </div>
  );
}
