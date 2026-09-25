import { Forbidden } from "@/components/shared/forbidden";
import { hasPermission } from "@/lib/auth/permissions";
import { getServerAuth } from "@/lib/auth/session";
import { ProgramsTable } from "./programs-table";

export default async function ProgramsPage() {
  const user = await getServerAuth();

  if (!hasPermission(user, "programs.viewAny")) {
    return <Forbidden message="You don't have permission to view programs." />;
  }

  return <ProgramsTable canCreate={hasPermission(user, "programs.create")} />;
}
