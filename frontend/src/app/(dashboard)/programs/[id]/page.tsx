import { Forbidden } from "@/components/shared/forbidden";
import { hasPermission } from "@/lib/auth/permissions";
import { getServerAuth } from "@/lib/auth/session";
import { ProgramDetail } from "./program-detail";

export default async function ProgramDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const user = await getServerAuth();

  if (!hasPermission(user, "programs.view")) {
    return <Forbidden message="You don't have permission to view this program." />;
  }

  return (
    <ProgramDetail
      programId={id}
      canUpdate={hasPermission(user, "programs.update")}
      canUpdateStatus={hasPermission(user, "programs.updateStatus")}
      canEnroll={hasPermission(user, "programs.enrollBeneficiary")}
      canCreateActivity={hasPermission(user, "activities.create")}
      canRecordAttendance={hasPermission(user, "activities.recordAttendance")}
    />
  );
}
