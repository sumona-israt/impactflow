import { Forbidden } from "@/components/shared/forbidden";
import { hasPermission } from "@/lib/auth/permissions";
import { getServerAuth } from "@/lib/auth/session";
import { RolesGrid } from "./roles-grid";

export default async function AdminRolesPage() {
  const user = await getServerAuth();

  if (!hasPermission(user, "roles.viewAny")) {
    return <Forbidden message="You don't have permission to view roles." />;
  }

  return <RolesGrid canManagePermissions={hasPermission(user, "roles.managePermissions")} />;
}
