import { Forbidden } from "@/components/shared/forbidden";
import { hasAnyPermission, hasPermission } from "@/lib/auth/permissions";
import { getServerAuth } from "@/lib/auth/session";
import { FinanceTabs } from "./finance-tabs";

export default async function FinancePage() {
  const user = await getServerAuth();

  if (!hasAnyPermission(user, ["expenses.viewAny", "assets.viewAny"])) {
    return <Forbidden message="You don't have permission to view finance or asset records." />;
  }

  return (
    <FinanceTabs
      canViewExpenses={hasPermission(user, "expenses.viewAny")}
      canCreateExpense={hasPermission(user, "expenses.create")}
      canViewAssets={hasPermission(user, "assets.viewAny")}
      canCreateAsset={hasPermission(user, "assets.create")}
      canAssignAsset={hasPermission(user, "assets.assign")}
    />
  );
}
