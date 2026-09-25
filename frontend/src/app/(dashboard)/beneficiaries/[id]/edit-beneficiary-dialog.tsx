"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { updateBeneficiary } from "@/lib/api/endpoints/beneficiaries";
import { ApiError } from "@/lib/api/errors";
import type { Beneficiary } from "@/types/beneficiaries";

interface FormValues {
  full_name: string;
  phone: string;
  district: string;
  upazila: string;
  emergency_contact_name: string;
  emergency_contact_phone: string;
  notes: string;
}

export function EditBeneficiaryDialog({
  beneficiary,
  open,
  onOpenChange,
}: {
  beneficiary: Beneficiary;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const queryClient = useQueryClient();
  const { register, handleSubmit, formState: { isSubmitting } } = useForm<FormValues>({
    values: {
      full_name: beneficiary.full_name,
      phone: beneficiary.phone ?? "",
      district: beneficiary.district ?? "",
      upazila: beneficiary.upazila ?? "",
      emergency_contact_name: beneficiary.emergency_contact_name ?? "",
      emergency_contact_phone: beneficiary.emergency_contact_phone ?? "",
      notes: beneficiary.notes ?? "",
    },
  });

  const mutation = useMutation({
    mutationFn: (values: FormValues) => updateBeneficiary(beneficiary.id, values),
    onSuccess: () => {
      toast.success("Beneficiary updated.");
      queryClient.invalidateQueries({ queryKey: ["beneficiaries", beneficiary.id] });
      onOpenChange(false);
    },
    onError: (error) => toast.error(error instanceof ApiError ? error.message : "Failed to update beneficiary."),
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg">
        <form onSubmit={handleSubmit((values) => mutation.mutate(values))}>
          <DialogHeader>
            <DialogTitle>Edit beneficiary</DialogTitle>
          </DialogHeader>
          <div className="grid grid-cols-2 gap-3 py-4">
            <div className="col-span-2 space-y-1.5">
              <Label htmlFor="edit-ben-name">Full name</Label>
              <Input id="edit-ben-name" {...register("full_name")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="edit-ben-phone">Phone</Label>
              <Input id="edit-ben-phone" {...register("phone")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="edit-ben-district">District</Label>
              <Input id="edit-ben-district" {...register("district")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="edit-ben-upazila">Upazila</Label>
              <Input id="edit-ben-upazila" {...register("upazila")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="edit-ben-ec-name">Emergency contact name</Label>
              <Input id="edit-ben-ec-name" {...register("emergency_contact_name")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="edit-ben-ec-phone">Emergency contact phone</Label>
              <Input id="edit-ben-ec-phone" {...register("emergency_contact_phone")} />
            </div>
            <div className="col-span-2 space-y-1.5">
              <Label htmlFor="edit-ben-notes">Notes</Label>
              <Input id="edit-ben-notes" {...register("notes")} />
            </div>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? "Saving…" : "Save changes"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
