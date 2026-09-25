import { Forbidden } from "@/components/shared/forbidden";
import { hasPermission } from "@/lib/auth/permissions";
import { getServerAuth } from "@/lib/auth/session";
import { BeneficiariesTable } from "./beneficiaries-table";

export default async function BeneficiariesPage() {
  const user = await getServerAuth();

  if (!hasPermission(user, "beneficiaries.viewAny")) {
    return <Forbidden message="You don't have permission to view beneficiaries." />;
  }

  return <BeneficiariesTable canCreate={hasPermission(user, "beneficiaries.create")} />;
}
