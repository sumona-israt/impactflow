import { Forbidden } from "@/components/shared/forbidden";
import { hasAnyPermission, hasPermission } from "@/lib/auth/permissions";
import { getServerAuth } from "@/lib/auth/session";
import { ReportsView } from "./reports-view";

const REPORT_TYPE_PERMISSIONS = [
  "programs.viewAny",
  "beneficiaries.view",
  "expenses.viewAny",
  "data-quality-issues.viewAny",
];

export default async function ReportsPage() {
  const user = await getServerAuth();

  if (!hasAnyPermission(user, REPORT_TYPE_PERMISSIONS)) {
    return <Forbidden message="You don't have permission to generate any report." />;
  }

  return (
    <ReportsView
      canProgramPerformance={hasPermission(user, "programs.viewAny")}
      canBeneficiaries={hasPermission(user, "beneficiaries.view")}
      canFinancial={hasPermission(user, "expenses.viewAny")}
      canDataQuality={hasPermission(user, "data-quality-issues.viewAny")}
    />
  );
}
