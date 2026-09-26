import { Forbidden } from "@/components/shared/forbidden";
import { hasAnyPermission, hasPermission } from "@/lib/auth/permissions";
import { getServerAuth } from "@/lib/auth/session";
import { DataQualityTabs } from "./data-quality-tabs";

export default async function DataQualityPage() {
  const user = await getServerAuth();

  if (!hasAnyPermission(user, ["data-imports.viewAny", "data-quality-issues.viewAny"])) {
    return <Forbidden message="You don't have permission to view data imports or quality issues." />;
  }

  return (
    <DataQualityTabs
      canViewImports={hasPermission(user, "data-imports.viewAny")}
      canCreateImport={hasPermission(user, "data-imports.create")}
      canViewIssues={hasPermission(user, "data-quality-issues.viewAny")}
      canManageIssues={hasPermission(user, "data-quality-issues.manage")}
    />
  );
}
