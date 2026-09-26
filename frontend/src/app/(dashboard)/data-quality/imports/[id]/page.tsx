import { Forbidden } from "@/components/shared/forbidden";
import { hasPermission } from "@/lib/auth/permissions";
import { getServerAuth } from "@/lib/auth/session";
import { ImportDetail } from "./import-detail";

export default async function ImportDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const user = await getServerAuth();

  if (!hasPermission(user, "data-imports.viewAny")) {
    return <Forbidden message="You don't have permission to view this import." />;
  }

  return <ImportDetail importId={id} canCreateImport={hasPermission(user, "data-imports.create")} />;
}
