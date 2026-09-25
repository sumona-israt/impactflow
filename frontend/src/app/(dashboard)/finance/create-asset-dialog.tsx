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
import { createAsset } from "@/lib/api/endpoints/assets";
import { ApiError } from "@/lib/api/errors";

const schema = z.object({
  name: z.string().min(1, "Name is required"),
  category: z.string().optional(),
  serial_number: z.string().optional(),
  purchase_date: z.string().optional(),
  purchase_value: z.string().optional(),
  location: z.string().optional(),
  condition: z.string().optional(),
});

type FormValues = z.infer<typeof schema>;

export function CreateAssetDialog() {
  const [open, setOpen] = useState(false);
  const queryClient = useQueryClient();

  const { register, handleSubmit, reset, setError, formState: { errors, isSubmitting } } = useForm<FormValues>({
    resolver: zodResolver(schema),
  });

  const mutation = useMutation({
    mutationFn: (values: FormValues) =>
      createAsset({
        name: values.name,
        category: values.category || undefined,
        serial_number: values.serial_number || undefined,
        purchase_date: values.purchase_date || undefined,
        purchase_value: values.purchase_value ? Number(values.purchase_value) : undefined,
        location: values.location || undefined,
        condition: values.condition || undefined,
      }),
    onSuccess: () => {
      toast.success("Asset added.");
      queryClient.invalidateQueries({ queryKey: ["assets"] });
      reset();
      setOpen(false);
    },
    onError: (error) => {
      if (error instanceof ApiError && error.status === 422) {
        setError("serial_number", { message: error.fieldError("serial_number") ?? error.message });
        return;
      }
      toast.error(error instanceof ApiError ? error.message : "Failed to add asset.");
    },
  });

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger className={buttonVariants({ size: "sm" })}>Add asset</DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <form onSubmit={handleSubmit((values) => mutation.mutate(values))} noValidate>
          <DialogHeader><DialogTitle>Add asset</DialogTitle></DialogHeader>
          <div className="grid grid-cols-2 gap-3 py-4">
            <div className="col-span-2 space-y-1.5">
              <Label htmlFor="asset-name">Name</Label>
              <Input id="asset-name" {...register("name")} aria-invalid={Boolean(errors.name)} />
              {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="asset-category">Category</Label>
              <Input id="asset-category" {...register("category")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="asset-serial">Serial number</Label>
              <Input id="asset-serial" {...register("serial_number")} aria-invalid={Boolean(errors.serial_number)} />
              {errors.serial_number && <p className="text-sm text-destructive">{errors.serial_number.message}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="asset-purchase-date">Purchase date</Label>
              <Input id="asset-purchase-date" type="date" {...register("purchase_date")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="asset-purchase-value">Purchase value</Label>
              <Input id="asset-purchase-value" type="number" step="0.01" {...register("purchase_value")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="asset-location">Location</Label>
              <Input id="asset-location" {...register("location")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="asset-condition">Condition</Label>
              <Input id="asset-condition" {...register("condition")} placeholder="new / good / fair" />
            </div>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={isSubmitting}>{isSubmitting ? "Adding…" : "Add asset"}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
