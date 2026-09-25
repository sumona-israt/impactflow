"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { useForm } from "react-hook-form";
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
import { createVolunteer } from "@/lib/api/endpoints/staff";
import { ApiError } from "@/lib/api/errors";

const schema = z.object({
  full_name: z.string().min(1, "Name is required"),
  phone: z.string().optional(),
  email: z.string().email("Enter a valid email").optional().or(z.literal("")),
  skills: z.string().optional(),
});

type FormValues = z.infer<typeof schema>;

export function CreateVolunteerDialog() {
  const [open, setOpen] = useState(false);
  const queryClient = useQueryClient();

  const { register, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm<FormValues>({
    resolver: zodResolver(schema),
  });

  const mutation = useMutation({
    mutationFn: (values: FormValues) =>
      createVolunteer({
        full_name: values.full_name,
        phone: values.phone || undefined,
        email: values.email || undefined,
        skills: values.skills ? values.skills.split(",").map((s) => s.trim()).filter(Boolean) : undefined,
      }),
    onSuccess: () => {
      toast.success("Volunteer added.");
      queryClient.invalidateQueries({ queryKey: ["volunteers"] });
      reset();
      setOpen(false);
    },
    onError: (error) => toast.error(error instanceof ApiError ? error.message : "Failed to add volunteer."),
  });

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger className={buttonVariants({ size: "sm" })}>Add volunteer</DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <form onSubmit={handleSubmit((values) => mutation.mutate(values))} noValidate>
          <DialogHeader><DialogTitle>Add volunteer</DialogTitle></DialogHeader>
          <div className="space-y-3 py-4">
            <div className="space-y-1.5">
              <Label htmlFor="vol-name">Full name</Label>
              <Input id="vol-name" {...register("full_name")} aria-invalid={Boolean(errors.full_name)} />
              {errors.full_name && <p className="text-sm text-destructive">{errors.full_name.message}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="vol-phone">Phone</Label>
              <Input id="vol-phone" {...register("phone")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="vol-email">Email</Label>
              <Input id="vol-email" type="email" {...register("email")} aria-invalid={Boolean(errors.email)} />
              {errors.email && <p className="text-sm text-destructive">{errors.email.message}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="vol-skills">Skills (comma-separated)</Label>
              <Input id="vol-skills" {...register("skills")} placeholder="teaching, first-aid" />
            </div>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={isSubmitting}>{isSubmitting ? "Adding…" : "Add volunteer"}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
