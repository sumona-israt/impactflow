import { hasPermission } from "@/lib/auth/permissions";
import { getServerAuth } from "@/lib/auth/session";
import { DashboardView } from "./dashboard-view";

export default async function DashboardPage() {
  const user = await getServerAuth();

  return (
    <DashboardView
      userName={user?.name.split(" ")[0]}
      canPrograms={hasPermission(user, "programs.viewAny")}
      canBeneficiaries={hasPermission(user, "beneficiaries.viewAny")}
      canStaffing={hasPermission(user, "employees.viewAny") || hasPermission(user, "volunteers.viewAny")}
      canActivities={hasPermission(user, "activities.viewAny")}
      canFinance={hasPermission(user, "expenses.viewAny")}
      canDataQuality={hasPermission(user, "data-quality-issues.viewAny")}
      canWorkflows={hasPermission(user, "workflows.viewAny")}
    />
  );
}
