import { Forbidden } from "@/components/shared/forbidden";
import { hasPermission } from "@/lib/auth/permissions";
import { getServerAuth } from "@/lib/auth/session";
import { OdooView } from "./odoo-view";

export default async function OdooPage() {
  const user = await getServerAuth();

  if (!hasPermission(user, "odoo.viewAny")) {
    return <Forbidden message="You don't have permission to view the Odoo Integration dashboard." />;
  }

  return (
    <OdooView
      canRetry={hasPermission(user, "odoo.retry")}
      canManageConfig={hasPermission(user, "odoo.manageConfig")}
    />
  );
}
