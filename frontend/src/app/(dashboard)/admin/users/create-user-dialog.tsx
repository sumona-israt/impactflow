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
import { createUser } from "@/lib/api/endpoints/users";
import { ApiError } from "@/lib/api/errors";
import { ROLE_OPTIONS, roleLabel } from "@/lib/rbac/labels";

const schema = z.object({
  name: z.string().min(1, "Name is required"),
  email: z.string().min(1, "Email is required").email("Enter a valid email address"),
  phone: z.string().optional(),
  password: z.string().min(8, "Password must be at least 8 characters"),
  roles: z.array(z.string()).min(1, "Select at least one role"),
});

type FormValues = z.infer<typeof schema>;

export function CreateUserDialog() {
  const [open, setOpen] = useState(false);
  const queryClient = useQueryClient();

  const {
    register,
    handleSubmit,
    watch,
    setValue,
    setError,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { name: "", email: "", phone: "", password: "", roles: [] },
  });

  const selectedRoles = watch("roles");

  const mutation = useMutation({
    mutationFn: createUser,
    onSuccess: () => {
      toast.success("User created.");
      queryClient.invalidateQueries({ queryKey: ["admin", "users"] });
      reset();
      setOpen(false);
    },
    onError: (error) => {
      if (error instanceof ApiError && error.status === 422) {
        setError("email", { message: error.fieldError("email") ?? error.message });
        return;
      }
      toast.error(error instanceof ApiError ? error.message : "Failed to create user.");
    },
  });

  function toggleRole(role: string, checked: boolean) {
    setValue("roles", checked ? [...selectedRoles, role] : selectedRoles.filter((r) => r !== role));
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger className={buttonVariants({ size: "sm" })}>Create user</DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <form onSubmit={handleSubmit((values) => mutation.mutate(values))} noValidate>
          <DialogHeader>
            <DialogTitle>Create user</DialogTitle>
          </DialogHeader>
          <div className="space-y-3 py-4">
            <div className="space-y-1.5">
              <Label htmlFor="create-name">Name</Label>
              <Input id="create-name" {...register("name")} aria-invalid={Boolean(errors.name)} />
              {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="create-email">Email</Label>
              <Input id="create-email" type="email" {...register("email")} aria-invalid={Boolean(errors.email)} />
              {errors.email && <p className="text-sm text-destructive">{errors.email.message}</p>}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="create-phone">Phone (optional)</Label>
              <Input id="create-phone" {...register("phone")} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="create-password">Temporary password</Label>
              <Input id="create-password" type="password" {...register("password")} aria-invalid={Boolean(errors.password)} />
              {errors.password && <p className="text-sm text-destructive">{errors.password.message}</p>}
            </div>
            <div className="space-y-1.5">
              <Label>Roles</Label>
              <div className="flex flex-wrap gap-2">
                {ROLE_OPTIONS.map((role) => (
                  <label
                    key={role}
                    className="flex items-center gap-1.5 rounded-md border px-2 py-1 text-xs has-checked:border-primary has-checked:bg-primary/5"
                  >
                    <input
                      type="checkbox"
                      className="size-3.5"
                      checked={selectedRoles.includes(role)}
                      onChange={(e) => toggleRole(role, e.target.checked)}
                    />
                    {roleLabel(role)}
                  </label>
                ))}
              </div>
              {errors.roles && <p className="text-sm text-destructive">{errors.roles.message}</p>}
            </div>
          </div>
          <DialogFooter>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? "Creating…" : "Create user"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
