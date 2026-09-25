"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { listAttendance, recordAttendance } from "@/lib/api/endpoints/activities";
import { listProgramBeneficiaries } from "@/lib/api/endpoints/programs";
import { ApiError } from "@/lib/api/errors";
import type { Activity } from "@/types/activities";

export function AttendanceDialog({
  activity,
  programId,
  open,
  onOpenChange,
}: {
  activity: Activity;
  programId: string;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const queryClient = useQueryClient();
  const [attendance, setAttendance] = useState<Record<string, boolean>>({});

  const enrollmentsQuery = useQuery({
    queryKey: ["program-beneficiaries", programId],
    queryFn: () => listProgramBeneficiaries(programId),
    enabled: open,
  });

  useQuery({
    queryKey: ["activity-attendance", activity.id],
    queryFn: async () => {
      const records = await listAttendance(activity.id);
      setAttendance(Object.fromEntries(records.map((r) => [r.beneficiary_id, r.attended])));
      return records;
    },
    enabled: open,
  });

  const mutation = useMutation({
    mutationFn: () => recordAttendance(activity.id, attendance),
    onSuccess: () => {
      toast.success("Attendance saved.");
      queryClient.invalidateQueries({ queryKey: ["program-activities", programId] });
      onOpenChange(false);
    },
    onError: (error) => {
      toast.error(error instanceof ApiError ? error.message : "Failed to save attendance.");
    },
  });

  const enrolled = enrollmentsQuery.data?.filter((e) => e.status === "enrolled") ?? [];

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>Attendance — {activity.title}</DialogTitle>
        </DialogHeader>
        <div className="flex max-h-64 flex-col gap-2 overflow-y-auto py-2">
          {enrolled.length === 0 && (
            <p className="text-sm text-muted-foreground">No beneficiaries are enrolled in this program yet.</p>
          )}
          {enrolled.map((enrollment) => {
            const beneficiary = enrollment.beneficiary!;
            return (
              <label key={beneficiary.id} className="flex items-center gap-2 rounded-md border px-3 py-2 text-sm">
                <input
                  type="checkbox"
                  checked={attendance[beneficiary.id] ?? false}
                  onChange={(e) => setAttendance((current) => ({ ...current, [beneficiary.id]: e.target.checked }))}
                />
                {beneficiary.full_name}
              </label>
            );
          })}
        </div>
        <DialogFooter>
          <Button onClick={() => mutation.mutate()} disabled={mutation.isPending || enrolled.length === 0}>
            {mutation.isPending ? "Saving…" : "Save attendance"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
