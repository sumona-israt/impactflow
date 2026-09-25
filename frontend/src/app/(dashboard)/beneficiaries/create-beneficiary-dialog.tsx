"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { z } from "zod";
import { Button, buttonVariants } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { createBeneficiary } from "@/lib/api/endpoints/beneficiaries";
import { ApiError } from "@/lib/api/errors";

const schema = z.object({
  full_name: z.string().min(1, "Name is required"),
  registration_date: z.string().min(1, "Registration date is required"),
  date_of_birth: z.string().optional(),
  gender: z.string().optional(),
  phone: z.string().optional(),
  district: z.string().optional(),
  upazila: z.string().optional(),
  emergency_contact_name: z.string().optional(),
  emergency_contact_phone: z.string().optional(),
});

type FormValues = z.infer<typeof schema>;

export function CreateBeneficiaryDialog() {
  const [open, setOpen] = useState(false);
  const router = useRouter();
  const queryClient = useQueryClient();

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { registration_date: new Date().toISOString().slice(0, 10) },
  });

  const mutation = useMutation({
    mutationFn: createBeneficiary,
    onSuccess: (beneficiary) => {
      toast.success("Beneficiary registered.");
      queryClient.invalidateQueries({ queryKey: ["beneficiaries"] });
      reset();
      setOpen(false);
      router.push(`/beneficiaries/${beneficiary.id}`);
    },
    onError: (error) => {
      if (error instanceof ApiError && error.status === 422) {
        setError("full_name", { message: error.fieldError("full_name") ?? error.message });
        return;
      }
      toast.error(error instanceof ApiError ? error.message : "Failed to register beneficiary.");
    },
  });

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger className={buttonVariants({ size: "sm" })}>Register beneficiary</DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <form onSubmit={handleSubmit((values) => mutation.mutate(values))} noValidate>
          <DialogHeader>
            <DialogTitle>Register beneficiary</DialogTitle>
          </DialogHeader>
          <div className="grid grid-cols-2 gap-3 py-4">
            <div className="col-span-2 space-y-1.5">
              <Label htmlFor="ben-name">Full name</Label>
              <Input id="ben-name" {...register("full_name")} aria-invalid={Boolean(errors.full_name)} />
              {errors.full_name && <p className="text-sm text-destructive">{errors.full_name.message}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="ben-dob">Date of birth</Label>
              <Input id="ben-dob" type="date" {...register("date_of_birth")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="ben-gender">Gender</Label>
              <Input id="ben-gender" {...register("gender")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="ben-phone">Phone</Label>
              <Input id="ben-phone" {...register("phone")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="ben-registration">Registration date</Label>
              <Input id="ben-registration" type="date" {...register("registration_date")} aria-invalid={Boolean(errors.registration_date)} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="ben-district">District</Label>
              <Input id="ben-district" {...register("district")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="ben-upazila">Upazila</Label>
              <Input id="ben-upazila" {...register("upazila")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="ben-ec-name">Emergency contact name</Label>
              <Input id="ben-ec-name" {...register("emergency_contact_name")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="ben-ec-phone">Emergency contact phone</Label>
              <Input id="ben-ec-phone" {...register("emergency_contact_phone")} />
            </div>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? "Registering…" : "Register beneficiary"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
