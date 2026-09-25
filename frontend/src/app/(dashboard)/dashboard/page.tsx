import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { getServerAuth } from "@/lib/auth/session";

const PLANNED_KPIS = [
  "Active Programs",
  "Total Beneficiaries",
  "Active Staff",
  "Active Volunteers",
  "Monthly Activities",
  "Total Budget",
  "Budget Utilization",
  "Pending Approvals",
];

export default async function DashboardPage() {
  const user = await getServerAuth();

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-lg font-semibold tracking-tight">
          Welcome back{user ? `, ${user.name.split(" ")[0]}` : ""}
        </h1>
        <p className="text-sm text-muted-foreground">
          Executive KPIs and charts land in Phase 6 once program, beneficiary, and finance
          data exist. Nothing below is fabricated — it reflects the current build state.
        </p>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {PLANNED_KPIS.map((title) => (
          <Card key={title}>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                {title}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-semibold text-muted-foreground/40">—</p>
              <p className="mt-1 text-xs text-muted-foreground">Available in Phase 6</p>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
