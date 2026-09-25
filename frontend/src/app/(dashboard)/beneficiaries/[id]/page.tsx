import { Forbidden } from "@/components/shared/forbidden";
import { hasPermission } from "@/lib/auth/permissions";
import { getServerAuth } from "@/lib/auth/session";
import { BeneficiaryDetail } from "./beneficiary-detail";

export default async function BeneficiaryDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const user = await getServerAuth();

  if (!hasPermission(user, "beneficiaries.view")) {
    return <Forbidden message="You don't have permission to view this beneficiary." />;
  }

  return <BeneficiaryDetail beneficiaryId={id} canUpdate={hasPermission(user, "beneficiaries.update")} />;
}
