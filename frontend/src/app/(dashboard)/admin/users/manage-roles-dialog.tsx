"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { syncUserRoles } from "@/lib/api/endpoints/users";
import { ApiError } from "@/lib/api/errors";
import { ROLE_OPTIONS, roleLabel } from "@/lib/rbac/labels";
import type { ManagedUser } from "@/types/rbac";

export function ManageRolesDialog({
  user,
  open,
  onOpenChange,
}: {
  user: ManagedUser;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  // The parent only mounts this dialog while `open`, so local state is
  // naturally fresh (from the current `user.roles`) every time it opens.
  const [roles, setRoles] = useState<string[]>(user.roles);
  const queryClient = useQueryClient();

  const mutation = useMutation({
    mutationFn: () => syncUserRoles(user.id, roles),
    onSuccess: () => {
      toast.success("Roles updated.");
      queryClient.invalidateQueries({ queryKey: ["admin", "users"] });
      onOpenChange(false);
    },
    onError: (error) => {
      toast.error(error instanceof ApiError ? error.message : "Failed to update roles.");
    },
  });

  function toggle(role: string, checked: boolean) {
    setRoles((current) => (checked ? [...current, role] : current.filter((r) => r !== role)));
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>Manage roles — {user.name}</DialogTitle>
        </DialogHeader>
        <div className="flex flex-col gap-2 py-2">
          {ROLE_OPTIONS.map((role) => (
            <label key={role} className="flex items-center gap-2 rounded-md border px-3 py-2 text-sm">
              <input
                type="checkbox"
                checked={roles.includes(role)}
                onChange={(e) => toggle(role, e.target.checked)}
              />
              {roleLabel(role)}
            </label>
          ))}
        </div>
        <DialogFooter>
          <Button onClick={() => mutation.mutate()} disabled={roles.length === 0 || mutation.isPending}>
            {mutation.isPending ? "Saving…" : "Save roles"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
