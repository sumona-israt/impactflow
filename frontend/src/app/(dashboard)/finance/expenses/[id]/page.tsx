import { Forbidden } from "@/components/shared/forbidden";
import { hasPermission } from "@/lib/auth/permissions";
import { getServerAuth } from "@/lib/auth/session";
import { ExpenseDetail } from "./expense-detail";

export default async function ExpenseDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const user = await getServerAuth();

  if (!hasPermission(user, "expenses.view")) {
    return <Forbidden message="You don't have permission to view this expense." />;
  }

  return <ExpenseDetail expenseId={id} currentUserId={user!.id} currentUserRoles={user!.roles} />;
}
