import { Forbidden } from "@/components/shared/forbidden";
import { hasPermission } from "@/lib/auth/permissions";
import { getServerAuth } from "@/lib/auth/session";
import { UsersTable } from "./users-table";

export default async function AdminUsersPage() {
  const user = await getServerAuth();

  if (!hasPermission(user, "users.viewAny")) {
    return <Forbidden message="You don't have permission to view users." />;
  }

  return <UsersTable canManageRoles={hasPermission(user, "users.manageRoles")} />;
}
