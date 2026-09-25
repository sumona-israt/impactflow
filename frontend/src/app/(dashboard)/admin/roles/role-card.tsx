"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { updateRolePermissions } from "@/lib/api/endpoints/roles";
import { ApiError } from "@/lib/api/errors";
import { permissionLabel, roleLabel } from "@/lib/rbac/labels";
import type { Permission, Role } from "@/types/rbac";

export function RoleCard({
  role,
  allPermissions,
  editable,
}: {
  role: Role;
  allPermissions: Permission[];
  editable: boolean;
}) {
  const [selected, setSelected] = useState<string[]>(role.permissions);
  const [syncedPermissions, setSyncedPermissions] = useState(role.permissions);
  const queryClient = useQueryClient();

  // Re-derive local selection when fresh data arrives (e.g. after a save
  // refetches the role) — adjusting state during render per React's
  // recommended pattern, rather than a useEffect that would set state
  // one render late. See https://react.dev/learn/you-might-not-need-an-effect
  if (syncedPermissions !== role.permissions) {
    setSyncedPermissions(role.permissions);
    setSelected(role.permissions);
  }

  const dirty = JSON.stringify([...selected].sort()) !== JSON.stringify([...role.permissions].sort());

  const mutation = useMutation({
    mutationFn: () => updateRolePermissions(role.id, selected),
    onSuccess: () => {
      toast.success(`Permissions updated for ${roleLabel(role.name)}.`);
      queryClient.invalidateQueries({ queryKey: ["admin", "roles"] });
    },
    onError: (error) => {
      toast.error(error instanceof ApiError ? error.message : "Failed to update permissions.");
    },
  });

  function toggle(permission: string, checked: boolean) {
    setSelected((current) => (checked ? [...current, permission] : current.filter((p) => p !== permission)));
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">{roleLabel(role.name)}</CardTitle>
      </CardHeader>
      <CardContent className="space-y-2">
        {allPermissions.length === 0 && (
          <p className="text-sm text-muted-foreground">No permissions defined yet.</p>
        )}
        {allPermissions.map((permission) => (
          <label key={permission.id} className="flex items-center gap-2 text-sm capitalize">
            <input
              type="checkbox"
              disabled={!editable}
              checked={selected.includes(permission.name)}
              onChange={(e) => toggle(permission.name, e.target.checked)}
            />
            {permissionLabel(permission.name)}
          </label>
        ))}
        {editable && (
          <Button
            size="sm"
            className="mt-2"
            disabled={!dirty || mutation.isPending}
            onClick={() => mutation.mutate()}
          >
            {mutation.isPending ? "Saving…" : "Save"}
          </Button>
        )}
      </CardContent>
    </Card>
  );
}
