import { Forbidden } from "@/components/shared/forbidden";
import { hasAnyPermission, hasPermission } from "@/lib/auth/permissions";
import { getServerAuth } from "@/lib/auth/session";
import { PeopleTabs } from "./people-tabs";

export default async function PeoplePage() {
  const user = await getServerAuth();

  if (!hasAnyPermission(user, ["employees.viewAny", "volunteers.viewAny"])) {
    return <Forbidden message="You don't have permission to view staff or volunteers." />;
  }

  return (
    <PeopleTabs
      canViewEmployees={hasPermission(user, "employees.viewAny")}
      canCreateEmployee={hasPermission(user, "employees.create")}
      canViewVolunteers={hasPermission(user, "volunteers.viewAny")}
      canCreateVolunteer={hasPermission(user, "volunteers.create")}
    />
  );
}
