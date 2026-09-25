import { Forbidden } from "@/components/shared/forbidden";
import { hasPermission } from "@/lib/auth/permissions";
import { getServerAuth } from "@/lib/auth/session";
import { AuditLogsTable } from "./audit-logs-table";

export default async function AdminAuditLogsPage() {
  const user = await getServerAuth();

  if (!hasPermission(user, "audit-logs.viewAny")) {
    return <Forbidden message="You don't have permission to view the audit trail." />;
  }

  return <AuditLogsTable />;
}
