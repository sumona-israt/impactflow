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
import { createProgramActivity } from "@/lib/api/endpoints/programs";
import { ApiError } from "@/lib/api/errors";

const schema = z.object({
  title: z.string().min(1, "Title is required"),
  scheduled_at: z.string().min(1, "Date/time is required"),
  location: z.string().optional(),
  description: z.string().optional(),
});

type FormValues = z.infer<typeof schema>;

export function CreateActivityDialog({ programId }: { programId: string }) {
  const [open, setOpen] = useState(false);
  const queryClient = useQueryClient();

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({ resolver: zodResolver(schema) });

  const mutation = useMutation({
    mutationFn: (values: FormValues) =>
      createProgramActivity(programId, {
        title: values.title,
        scheduled_at: values.scheduled_at,
        location: values.location || undefined,
        description: values.description || undefined,
      }),
    onSuccess: () => {
      toast.success("Activity created.");
      queryClient.invalidateQueries({ queryKey: ["program-activities", programId] });
      reset();
      setOpen(false);
    },
    onError: (error) => {
      toast.error(error instanceof ApiError ? error.message : "Failed to create activity.");
    },
  });

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger className={buttonVariants({ size: "sm", variant: "outline" })}>Add activity</DialogTrigger>
      <DialogContent className="sm:max-w-sm">
        <form onSubmit={handleSubmit((values) => mutation.mutate(values))} noValidate>
          <DialogHeader>
            <DialogTitle>Add activity</DialogTitle>
          </DialogHeader>
          <div className="space-y-3 py-4">
            <div className="space-y-1.5">
              <Label htmlFor="activity-title">Title</Label>
              <Input id="activity-title" {...register("title")} aria-invalid={Boolean(errors.title)} />
              {errors.title && <p className="text-sm text-destructive">{errors.title.message}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="activity-scheduled">Date &amp; time</Label>
              <Input id="activity-scheduled" type="datetime-local" {...register("scheduled_at")} aria-invalid={Boolean(errors.scheduled_at)} />
              {errors.scheduled_at && <p className="text-sm text-destructive">{errors.scheduled_at.message}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="activity-location">Location</Label>
              <Input id="activity-location" {...register("location")} />
            </div>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? "Creating…" : "Add activity"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
