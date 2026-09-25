import { redirect } from "next/navigation";
import { Forbidden } from "@/components/shared/forbidden";
import { hasAnyPermission, hasPermission } from "@/lib/auth/permissions";
import { getServerAuth } from "@/lib/auth/session";
import { AdminTabs } from "./admin-tabs";

export default async function AdminLayout({ children }: { children: React.ReactNode }) {
  const user = await getServerAuth();

  if (!user) {
    redirect("/login");
  }

  const canViewUsers = hasPermission(user, "users.viewAny");
  const canViewRoles = hasPermission(user, "roles.viewAny");
  const canViewAuditLogs = hasPermission(user, "audit-logs.viewAny");

  if (!hasAnyPermission(user, ["users.viewAny", "roles.viewAny", "audit-logs.viewAny"])) {
    return <Forbidden message="Administration is restricted to users with Super Administrator access." />;
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-lg font-semibold tracking-tight">Administration</h1>
        <p className="text-sm text-muted-foreground">
          User management, role permissions, and the system audit trail.
        </p>
      </div>
      <AdminTabs
        tabs={[
          ...(canViewUsers ? [{ href: "/admin/users", label: "Users" }] : []),
          ...(canViewRoles ? [{ href: "/admin/roles", label: "Roles & Permissions" }] : []),
          ...(canViewAuditLogs ? [{ href: "/admin/audit-logs", label: "Audit Logs" }] : []),
        ]}
      />
      {children}
    </div>
  );
}
