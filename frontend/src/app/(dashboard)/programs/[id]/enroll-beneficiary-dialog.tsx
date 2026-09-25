"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { toast } from "sonner";
import { BeneficiaryPicker } from "@/components/shared/beneficiary-picker";
import { buttonVariants } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { enrollBeneficiary } from "@/lib/api/endpoints/programs";
import { ApiError } from "@/lib/api/errors";
import type { BeneficiaryListItem } from "@/types/beneficiaries";

export function EnrollBeneficiaryDialog({ programId }: { programId: string }) {
  const [open, setOpen] = useState(false);
  const queryClient = useQueryClient();

  const mutation = useMutation({
    mutationFn: (beneficiary: BeneficiaryListItem) => enrollBeneficiary(programId, beneficiary.id),
    onSuccess: () => {
      toast.success("Beneficiary enrolled.");
      queryClient.invalidateQueries({ queryKey: ["programs", programId] });
      queryClient.invalidateQueries({ queryKey: ["program-beneficiaries", programId] });
      setOpen(false);
    },
    onError: (error) => {
      toast.error(error instanceof ApiError ? error.message : "Failed to enroll beneficiary.");
    },
  });

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger className={buttonVariants({ size: "sm", variant: "outline" })}>Enroll beneficiary</DialogTrigger>
      <DialogContent className="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>Enroll a beneficiary</DialogTitle>
        </DialogHeader>
        <BeneficiaryPicker onSelect={(beneficiary) => mutation.mutate(beneficiary)} />
      </DialogContent>
    </Dialog>
  );
}
