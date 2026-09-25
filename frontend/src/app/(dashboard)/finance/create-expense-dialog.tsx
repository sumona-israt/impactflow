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
import { listExpenseCategories, createExpense } from "@/lib/api/endpoints/expenses";
import { listPrograms } from "@/lib/api/endpoints/programs";
import { ApiError } from "@/lib/api/errors";

const schema = z.object({
  program_id: z.string().min(1, "Program is required"),
  category_id: z.string().optional(),
  amount: z.string().min(1, "Amount is required"),
  expense_date: z.string().min(1, "Date is required"),
  description: z.string().optional(),
});

type FormValues = z.infer<typeof schema>;

export function CreateExpenseDialog() {
  const [open, setOpen] = useState(false);
  const router = useRouter();
  const queryClient = useQueryClient();

  const programsQuery = useQuery({
    queryKey: ["programs-picker"],
    queryFn: () => listPrograms({ page: 1 }),
    enabled: open,
  });
  const categoriesQuery = useQuery({
    queryKey: ["lookups", "expense-categories"],
    queryFn: listExpenseCategories,
    enabled: open,
  });

  const {
    register,
    handleSubmit,
    control,
    reset,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { expense_date: new Date().toISOString().slice(0, 10) },
  });

  const mutation = useMutation({
    mutationFn: (values: FormValues) =>
      createExpense({
        program_id: values.program_id,
        category_id: values.category_id ? Number(values.category_id) : undefined,
        amount: Number(values.amount),
        expense_date: values.expense_date,
        description: values.description || undefined,
      }),
    onSuccess: (expense) => {
      toast.success("Expense created as draft.");
      queryClient.invalidateQueries({ queryKey: ["expenses"] });
      reset();
      setOpen(false);
      router.push(`/finance/expenses/${expense.id}`);
    },
    onError: (error) => {
      if (error instanceof ApiError && error.status === 422) {
        setError("amount", { message: error.fieldError("amount") ?? error.message });
        return;
      }
      toast.error(error instanceof ApiError ? error.message : "Failed to create expense.");
    },
  });

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger className={buttonVariants({ size: "sm" })}>New expense</DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <form onSubmit={handleSubmit((values) => mutation.mutate(values))} noValidate>
          <DialogHeader>
            <DialogTitle>New expense</DialogTitle>
          </DialogHeader>
          <div className="space-y-3 py-4">
            <div className="space-y-1.5">
              <Label>Program</Label>
              <Controller
                control={control}
                name="program_id"
                render={({ field }) => (
                  <Select value={field.value} onValueChange={field.onChange}>
                    <SelectTrigger className="w-full"><SelectValue placeholder="Select program" /></SelectTrigger>
                    <SelectContent>
                      {programsQuery.data?.data.map((p) => (
                        <SelectItem key={p.id} value={p.id}>{p.name}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                )}
              />
              {errors.program_id && <p className="text-sm text-destructive">{errors.program_id.message}</p>}
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
              <Label htmlFor="expense-amount">Amount</Label>
              <Input id="expense-amount" type="number" step="0.01" {...register("amount")} aria-invalid={Boolean(errors.amount)} />
              {errors.amount && <p className="text-sm text-destructive">{errors.amount.message}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="expense-date">Expense date</Label>
              <Input id="expense-date" type="date" {...register("expense_date")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="expense-description">Description</Label>
              <Input id="expense-description" {...register("description")} />
            </div>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? "Creating…" : "Create draft"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
