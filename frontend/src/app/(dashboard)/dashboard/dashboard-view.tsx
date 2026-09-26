"use client";

import { useQuery } from "@tanstack/react-query";
import {
  Area,
  AreaChart,
  Bar,
  BarChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { getDashboardKpis } from "@/lib/api/endpoints/dashboard";
import { formatCurrency, statusLabel } from "@/lib/format";

interface StatTile {
  label: string;
  value: string;
}

function monthLabel(key: string): string {
  const [year, month] = key.split("-").map(Number);
  return new Date(year, month - 1, 1).toLocaleDateString(undefined, { month: "short" });
}

function StatTiles({ tiles }: { tiles: StatTile[] }) {
  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      {tiles.map((tile) => (
        <Card key={tile.label}>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">{tile.label}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-semibold">{tile.value}</p>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

const tooltipStyle = {
  backgroundColor: "var(--popover)",
  color: "var(--popover-foreground)",
  border: "1px solid var(--border)",
  borderRadius: "var(--radius-md)",
  fontSize: 12,
};

export function DashboardView({
  userName,
  canPrograms,
  canBeneficiaries,
  canStaffing,
  canActivities,
  canFinance,
  canDataQuality,
  canWorkflows,
}: {
  userName?: string;
  canPrograms: boolean;
  canBeneficiaries: boolean;
  canStaffing: boolean;
  canActivities: boolean;
  canFinance: boolean;
  canDataQuality: boolean;
  canWorkflows: boolean;
}) {
  const { data, isLoading } = useQuery({
    queryKey: ["dashboard-kpis"],
    queryFn: getDashboardKpis,
  });

  const tiles: StatTile[] = [];
  if (canPrograms && data?.programs) {
    tiles.push({ label: "Active Programs", value: String(data.programs.active_programs) });
  }
  if (canBeneficiaries && data?.beneficiaries) {
    tiles.push({ label: "Total Beneficiaries", value: String(data.beneficiaries.total_beneficiaries) });
  }
  if (canStaffing && data?.staffing?.active_employees !== undefined) {
    tiles.push({ label: "Active Staff", value: String(data.staffing.active_employees) });
  }
  if (canStaffing && data?.staffing?.active_volunteers !== undefined) {
    tiles.push({ label: "Active Volunteers", value: String(data.staffing.active_volunteers) });
  }
  if (canActivities && data?.activities) {
    tiles.push({ label: "Monthly Activities", value: String(data.activities.activities_this_month) });
  }
  if (canFinance && data?.finance) {
    tiles.push({ label: "Total Budget", value: formatCurrency(data.finance.total_budget) });
    tiles.push({ label: "Budget Utilization", value: `${data.finance.budget_utilization_pct}%` });
  }
  if (canWorkflows && data?.workflows) {
    tiles.push({ label: "Pending Approvals", value: String(data.workflows.pending_approvals) });
  }
  if (canDataQuality && data?.data_quality) {
    tiles.push({ label: "Data Quality Score", value: `${data.data_quality.score}%` });
  }

  const statusData =
    canPrograms && data?.programs
      ? Object.entries(data.programs.status_breakdown).map(([status, count]) => ({
          status: statusLabel(status),
          count,
        }))
      : [];

  const enrollmentData =
    canBeneficiaries && data?.beneficiaries
      ? Object.entries(data.beneficiaries.enrolled_by_month).map(([month, count]) => ({
          month: monthLabel(month),
          count,
        }))
      : [];

  const expensesByCategory =
    canFinance && data?.finance
      ? Object.entries(data.finance.expenses_by_category)
          .map(([category, amount]) => ({ category, amount }))
          .sort((a, b) => b.amount - a.amount)
      : [];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-lg font-semibold tracking-tight">Welcome back{userName ? `, ${userName}` : ""}</h1>
        <p className="text-sm text-muted-foreground">
          Live KPIs and charts drawn from current program, beneficiary, and finance data.
        </p>
      </div>

      {isLoading ? (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {Array.from({ length: 4 }).map((_, i) => (
            <Skeleton key={i} className="h-24 w-full" />
          ))}
        </div>
      ) : tiles.length > 0 ? (
        <StatTiles tiles={tiles} />
      ) : (
        <p className="text-sm text-muted-foreground">No KPIs available for your role yet.</p>
      )}

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        {statusData.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle className="text-sm font-medium">Programs by status</CardTitle>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={240}>
                <BarChart data={statusData} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" vertical={false} />
                  <XAxis
                    dataKey="status"
                    tick={{ fill: "var(--muted-foreground)", fontSize: 11 }}
                    axisLine={{ stroke: "var(--border)" }}
                    tickLine={false}
                  />
                  <YAxis
                    allowDecimals={false}
                    tick={{ fill: "var(--muted-foreground)", fontSize: 11 }}
                    axisLine={false}
                    tickLine={false}
                    width={28}
                  />
                  <Tooltip contentStyle={tooltipStyle} cursor={{ fill: "var(--muted)" }} />
                  <Bar dataKey="count" fill="var(--chart-1)" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>
        )}

        {enrollmentData.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle className="text-sm font-medium">Beneficiaries enrolled by month</CardTitle>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={240}>
                <AreaChart data={enrollmentData} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" vertical={false} />
                  <XAxis
                    dataKey="month"
                    tick={{ fill: "var(--muted-foreground)", fontSize: 11 }}
                    axisLine={{ stroke: "var(--border)" }}
                    tickLine={false}
                  />
                  <YAxis
                    allowDecimals={false}
                    tick={{ fill: "var(--muted-foreground)", fontSize: 11 }}
                    axisLine={false}
                    tickLine={false}
                    width={28}
                  />
                  <Tooltip contentStyle={tooltipStyle} cursor={{ stroke: "var(--border)" }} />
                  <Area
                    type="monotone"
                    dataKey="count"
                    stroke="var(--chart-1)"
                    fill="var(--chart-1)"
                    fillOpacity={0.15}
                    strokeWidth={2}
                  />
                </AreaChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>
        )}

        {expensesByCategory.length > 0 && (
          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle className="text-sm font-medium">Approved expenses by category</CardTitle>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={Math.max(160, expensesByCategory.length * 40)}>
                <BarChart
                  data={expensesByCategory}
                  layout="vertical"
                  margin={{ top: 8, right: 16, left: 0, bottom: 0 }}
                >
                  <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" horizontal={false} />
                  <XAxis
                    type="number"
                    tick={{ fill: "var(--muted-foreground)", fontSize: 11 }}
                    axisLine={{ stroke: "var(--border)" }}
                    tickLine={false}
                  />
                  <YAxis
                    dataKey="category"
                    type="category"
                    width={120}
                    tick={{ fill: "var(--muted-foreground)", fontSize: 11 }}
                    axisLine={false}
                    tickLine={false}
                  />
                  <Tooltip
                    contentStyle={tooltipStyle}
                    cursor={{ fill: "var(--muted)" }}
                    formatter={(value) => formatCurrency(Number(value))}
                  />
                  <Bar dataKey="amount" fill="var(--chart-2)" radius={[0, 4, 4, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>
        )}
      </div>
    </div>
  );
}
