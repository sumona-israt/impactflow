"use client";

import { useQuery } from "@tanstack/react-query";
import { Skeleton } from "@/components/ui/skeleton";
import { listPermissions, listRoles } from "@/lib/api/endpoints/roles";
import { RoleCard } from "./role-card";

export function RolesGrid({ canManagePermissions }: { canManagePermissions: boolean }) {
  const rolesQuery = useQuery({ queryKey: ["admin", "roles"], queryFn: listRoles });
  const permissionsQuery = useQuery({ queryKey: ["admin", "permissions"], queryFn: listPermissions });

  if (rolesQuery.isLoading || permissionsQuery.isLoading) {
    return (
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {Array.from({ length: 6 }).map((_, i) => (
          <Skeleton key={i} className="h-40 w-full" />
        ))}
      </div>
    );
  }

  if (rolesQuery.isError || permissionsQuery.isError) {
    return <p className="text-sm text-destructive">Failed to load roles and permissions.</p>;
  }

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      {rolesQuery.data?.map((role) => (
        <RoleCard
          key={role.id}
          role={role}
          allPermissions={permissionsQuery.data ?? []}
          editable={canManagePermissions}
        />
      ))}
    </div>
  );
}
