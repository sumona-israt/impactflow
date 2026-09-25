"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Controller, useForm } from "react-hook-form";
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
import { listBranches, listDepartments } from "@/lib/api/endpoints/lookups";
import { createEmployee } from "@/lib/api/endpoints/staff";
import { ApiError } from "@/lib/api/errors";

const schema = z.object({
  name: z.string().min(1, "Name is required"),
  position: z.string().optional(),
  department_id: z.string().optional(),
  branch_id: z.string().optional(),
  joining_date: z.string().optional(),
});

type FormValues = z.infer<typeof schema>;

export function CreateEmployeeDialog() {
  const [open, setOpen] = useState(false);
  const queryClient = useQueryClient();

  const departmentsQuery = useQuery({ queryKey: ["lookups", "departments"], queryFn: listDepartments, enabled: open });
  const branchesQuery = useQuery({ queryKey: ["lookups", "branches"], queryFn: listBranches, enabled: open });

  const { register, handleSubmit, control, reset, formState: { errors, isSubmitting } } = useForm<FormValues>({
    resolver: zodResolver(schema),
  });

  const mutation = useMutation({
    mutationFn: (values: FormValues) =>
      createEmployee({
        name: values.name,
        position: values.position || undefined,
        department_id: values.department_id ? Number(values.department_id) : undefined,
        branch_id: values.branch_id ? Number(values.branch_id) : undefined,
        joining_date: values.joining_date || undefined,
      }),
    onSuccess: () => {
      toast.success("Employee added.");
      queryClient.invalidateQueries({ queryKey: ["employees"] });
      reset();
      setOpen(false);
    },
    onError: (error) => toast.error(error instanceof ApiError ? error.message : "Failed to add employee."),
  });

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger className={buttonVariants({ size: "sm" })}>Add employee</DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <form onSubmit={handleSubmit((values) => mutation.mutate(values))} noValidate>
          <DialogHeader><DialogTitle>Add employee</DialogTitle></DialogHeader>
          <div className="space-y-3 py-4">
            <div className="space-y-1.5">
              <Label htmlFor="emp-name">Name</Label>
              <Input id="emp-name" {...register("name")} aria-invalid={Boolean(errors.name)} />
              {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="emp-position">Position</Label>
              <Input id="emp-position" {...register("position")} />
            </div>
            <div className="space-y-1.5">
              <Label>Department</Label>
              <Controller control={control} name="department_id" render={({ field }) => (
                <Select value={field.value} onValueChange={field.onChange}>
                  <SelectTrigger className="w-full"><SelectValue placeholder="Select department" /></SelectTrigger>
                  <SelectContent>
                    {departmentsQuery.data?.map((d) => <SelectItem key={d.id} value={String(d.id)}>{d.name}</SelectItem>)}
                  </SelectContent>
                </Select>
              )} />
            </div>
            <div className="space-y-1.5">
              <Label>Branch</Label>
              <Controller control={control} name="branch_id" render={({ field }) => (
                <Select value={field.value} onValueChange={field.onChange}>
                  <SelectTrigger className="w-full"><SelectValue placeholder="Select branch" /></SelectTrigger>
                  <SelectContent>
                    {branchesQuery.data?.map((b) => <SelectItem key={b.id} value={String(b.id)}>{b.name}</SelectItem>)}
                  </SelectContent>
                </Select>
              )} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="emp-joining">Joining date</Label>
              <Input id="emp-joining" type="date" {...register("joining_date")} />
            </div>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={isSubmitting}>{isSubmitting ? "Adding…" : "Add employee"}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
