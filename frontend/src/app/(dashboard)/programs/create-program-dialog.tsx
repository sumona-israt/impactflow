"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Controller, useForm } from "react-hook-form";
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { listBranches, listProgramCategories } from "@/lib/api/endpoints/lookups";
import { createProgram } from "@/lib/api/endpoints/programs";
import { ApiError } from "@/lib/api/errors";

const schema = z.object({
  name: z.string().min(1, "Name is required"),
  description: z.string().optional(),
  category_id: z.string().optional(),
  branch_id: z.string().optional(),
  district: z.string().optional(),
  start_date: z.string().min(1, "Start date is required"),
  end_date: z.string().optional(),
  budget: z.string().optional(),
  target_beneficiaries: z.string().optional(),
});

type FormValues = z.infer<typeof schema>;

export function CreateProgramDialog() {
  const [open, setOpen] = useState(false);
  const router = useRouter();
  const queryClient = useQueryClient();

  const categoriesQuery = useQuery({ queryKey: ["lookups", "program-categories"], queryFn: listProgramCategories, enabled: open });
  const branchesQuery = useQuery({ queryKey: ["lookups", "branches"], queryFn: listBranches, enabled: open });

  const {
    register,
    handleSubmit,
    control,
    reset,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({ resolver: zodResolver(schema), defaultValues: { name: "" } });

  const mutation = useMutation({
    mutationFn: (values: FormValues) =>
      createProgram({
        name: values.name,
        description: values.description || undefined,
        category_id: values.category_id ? Number(values.category_id) : undefined,
        branch_id: values.branch_id ? Number(values.branch_id) : undefined,
        district: values.district || undefined,
        start_date: values.start_date,
        end_date: values.end_date || undefined,
        budget: values.budget ? Number(values.budget) : undefined,
        target_beneficiaries: values.target_beneficiaries ? Number(values.target_beneficiaries) : undefined,
      }),
    onSuccess: (program) => {
      toast.success("Program created.");
      queryClient.invalidateQueries({ queryKey: ["programs"] });
      reset();
      setOpen(false);
      router.push(`/programs/${program.id}`);
    },
    onError: (error) => {
      if (error instanceof ApiError && error.status === 422) {
        setError("name", { message: error.fieldError("name") ?? error.message });
        return;
      }
      toast.error(error instanceof ApiError ? error.message : "Failed to create program.");
    },
  });

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger className={buttonVariants({ size: "sm" })}>Create program</DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <form onSubmit={handleSubmit((values) => mutation.mutate(values))} noValidate>
          <DialogHeader>
            <DialogTitle>Create program</DialogTitle>
          </DialogHeader>
          <div className="grid grid-cols-2 gap-3 py-4">
            <div className="col-span-2 space-y-1.5">
              <Label htmlFor="program-name">Name</Label>
              <Input id="program-name" {...register("name")} aria-invalid={Boolean(errors.name)} />
              {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
            </div>
            <div className="col-span-2 space-y-1.5">
              <Label htmlFor="program-description">Description</Label>
              <Input id="program-description" {...register("description")} />
            </div>
            <div className="space-y-1.5">
              <Label>Category</Label>
              <Controller
                control={control}
                name="category_id"
                render={({ field }) => (
                  <Select value={field.value} onValueChange={field.onChange}>
                    <SelectTrigger className="w-full"><SelectValue placeholder="Select category" /></SelectTrigger>
                    <SelectContent>
                      {categoriesQuery.data?.map((c) => (
                        <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                )}
              />
            </div>
            <div className="space-y-1.5">
              <Label>Branch</Label>
              <Controller
                control={control}
                name="branch_id"
                render={({ field }) => (
                  <Select value={field.value} onValueChange={field.onChange}>
                    <SelectTrigger className="w-full"><SelectValue placeholder="Select branch" /></SelectTrigger>
                    <SelectContent>
                      {branchesQuery.data?.map((b) => (
                        <SelectItem key={b.id} value={String(b.id)}>{b.name}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                )}
              />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="program-district">District</Label>
              <Input id="program-district" {...register("district")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="program-budget">Budget (BDT)</Label>
              <Input id="program-budget" type="number" step="0.01" {...register("budget")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="program-start">Start date</Label>
              <Input id="program-start" type="date" {...register("start_date")} aria-invalid={Boolean(errors.start_date)} />
              {errors.start_date && <p className="text-sm text-destructive">{errors.start_date.message}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="program-end">End date</Label>
              <Input id="program-end" type="date" {...register("end_date")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="program-target">Target beneficiaries</Label>
              <Input id="program-target" type="number" {...register("target_beneficiaries")} />
            </div>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? "Creating…" : "Create program"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
