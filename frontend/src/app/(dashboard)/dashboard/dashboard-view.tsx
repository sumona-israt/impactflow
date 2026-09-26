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
import { Skeleton } from "@/components/ui/skeleton";
import { getDashboardKpis } from "@/lib/api/endpoints/dashboard";
import { formatCurrency, statusLabel } from "@/lib/format";

interface StatTile {
  label: string;
  value: string;
  subtext?: string;
  trend?: { label: string; positive: boolean };
  icon: React.ReactNode;
  progress?: number;
  alert?: boolean;
}

function monthLabel(key: string): string {
  const [year, month] = key.split("-").map(Number);
  return new Date(year, month - 1, 1).toLocaleDateString(undefined, { month: "short" });
}

const tooltipStyle = {
  backgroundColor: "#ffffff",
  color: "#0f172a",
  border: "1px solid #e2e8f0",
  borderRadius: "8px",
  fontSize: 12,
  boxShadow: "0 4px 6px -1px rgba(30,42,94,0.06)",
};

function TrendBadge({ label, positive }: { label: string; positive: boolean }) {
  return (
    <span
      className="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[11px] font-semibold"
      style={
        positive
          ? { background: "#ecfdf5", color: "#047857" }
          : { background: "#fff1f2", color: "#be123c" }
      }
    >
      <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
        {positive ? <path d="M4 12l1.41 1.41L11 7.83V20h2V7.83l5.58 5.59L20 12l-8-8-8 8z" /> : <path d="M20 12l-1.41-1.41L13 16.17V4h-2v12.17l-5.58-5.59L4 12l8 8 8-8z" />}
      </svg>
      {label}
    </span>
  );
}

function StatTiles({ tiles }: { tiles: StatTile[] }) {
  return (
    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      {tiles.map((tile) => (
        <div
          key={tile.label}
          className="bg-white border border-slate-200 rounded-xl p-5 shadow-[0_1px_2px_0_rgba(15,23,42,0.04)] hover:border-slate-300 hover:shadow-[0_4px_6px_-1px_rgba(30,42,94,0.06)] transition-all duration-150"
        >
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold uppercase tracking-wider text-slate-400">
              {tile.label}
            </span>
            <span className="text-[#3e59ae] opacity-70">{tile.icon}</span>
          </div>

          <div className="mt-3 flex items-baseline gap-3 flex-wrap">
            <span
              className="text-[32px] font-bold tracking-tight font-tabular"
              style={{ color: "#1e2a5e", lineHeight: 1.1 }}
            >
              {tile.value}
            </span>
            {tile.trend && (
              <TrendBadge label={tile.trend.label} positive={tile.trend.positive} />
            )}
            {tile.alert && (
              <span
                className="inline-flex items-center gap-1 px-2 py-0.5 rounded border text-[11px] font-semibold"
                style={{ background: "#fffbeb", color: "#b45309", borderColor: "#fde68a" }}
              >
                Requires Review
              </span>
            )}
          </div>

          {tile.progress !== undefined && (
            <div className="mt-2">
              <div className="w-full h-1.5 rounded-full overflow-hidden" style={{ background: "#e5eeff" }}>
                <div
                  className="h-1.5 rounded-full transition-all duration-500"
                  style={{ width: `${tile.progress}%`, background: "#3e59ae" }}
                />
              </div>
            </div>
          )}

          {tile.subtext && (
            <div className="mt-2 text-[12px] text-slate-400 flex items-center gap-1">
              {tile.subtext}
            </div>
          )}
        </div>
      ))}
    </div>
  );
}

function SectionCard({
  title,
  subtitle,
  children,
  wide,
}: {
  title: string;
  subtitle?: string;
  children: React.ReactNode;
  wide?: boolean;
}) {
  return (
    <div
      className={`bg-white border border-slate-200 rounded-xl shadow-[0_1px_2px_0_rgba(15,23,42,0.04)] overflow-hidden${wide ? " lg:col-span-2" : ""}`}
    >
      <div className="px-5 py-4 border-b border-slate-100">
        <h3 className="text-[15px] font-semibold text-[#1e2a5e]">{title}</h3>
        {subtitle && <p className="text-[12px] text-slate-400 mt-0.5">{subtitle}</p>}
      </div>
      <div className="p-5">{children}</div>
    </div>
  );
}

// Icon components
function ProgramIcon() {
  return (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
      <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
    </svg>
  );
}
function BeneficiaryIcon() {
  return (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
      <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" />
    </svg>
  );
}
function BudgetIcon() {
  return (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
      <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" />
    </svg>
  );
}
function ApprovalIcon() {
  return (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style={{ color: "#d97706" }}>
      <path d="M11 15h2v2h-2zm0-8h2v6h-2zm.99-5C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z" />
    </svg>
  );
}
function StaffIcon() {
  return (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
      <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
    </svg>
  );
}
function DataQualityIcon() {
  return (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
      <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z" />
    </svg>
  );
}
function WorkflowIcon() {
  return (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style={{ color: "#d97706" }}>
      <path d="M11 15h2v2h-2zm0-8h2v6h-2zm.99-5C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z" />
    </svg>
  );
}
function VolunteerIcon() {
  return (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
      <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z" />
    </svg>
  );
}

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
    tiles.push({
      label: "Active Programs",
      value: String(data.programs.active_programs),
      icon: <ProgramIcon />,
      subtext: "Across active field missions",
    });
  }
  if (canBeneficiaries && data?.beneficiaries) {
    tiles.push({
      label: "Total Beneficiaries",
      value: String(data.beneficiaries.total_beneficiaries),
      icon: <BeneficiaryIcon />,
      subtext: "Verified biometric & household ID",
    });
  }
  if (canStaffing && data?.staffing?.active_employees !== undefined) {
    tiles.push({
      label: "Active Staff",
      value: String(data.staffing.active_employees),
      icon: <StaffIcon />,
      subtext: "Full-time employees",
    });
  }
  if (canStaffing && data?.staffing?.active_volunteers !== undefined) {
    tiles.push({
      label: "Active Volunteers",
      value: String(data.staffing.active_volunteers),
      icon: <VolunteerIcon />,
      subtext: "Volunteer workforce",
    });
  }
  if (canActivities && data?.activities) {
    tiles.push({
      label: "Monthly Activities",
      value: String(data.activities.activities_this_month),
      icon: <ProgramIcon />,
      subtext: "This month",
    });
  }
  if (canFinance && data?.finance) {
    tiles.push({
      label: "Total Budget",
      value: formatCurrency(data.finance.total_budget),
      icon: <BudgetIcon />,
      subtext: "Total allocated budget",
    });
    const util = data.finance.budget_utilization_pct;
    tiles.push({
      label: "Budget Utilization",
      value: `${util}%`,
      icon: <BudgetIcon />,
      progress: util,
      subtext: util >= 70 ? "On track" : "Below target",
    });
  }
  if (canWorkflows && data?.workflows) {
    tiles.push({
      label: "Pending Approvals",
      value: String(data.workflows.pending_approvals),
      icon: <WorkflowIcon />,
      alert: data.workflows.pending_approvals > 0,
      subtext: "Awaiting your review",
    });
  }
  if (canDataQuality && data?.data_quality) {
    const score = data.data_quality.score;
    tiles.push({
      label: "Data Quality Score",
      value: `${score}%`,
      icon: <DataQualityIcon />,
      progress: score,
      trend: { label: score >= 80 ? "Good" : "Needs attention", positive: score >= 80 },
    });
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
      {/* Page header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-3">
            <h1 className="text-[24px] font-bold tracking-tight" style={{ color: "#1e2a5e" }}>
              Welcome back{userName ? `, ${userName}` : ""}
            </h1>
            <span
              className="px-2 py-0.5 rounded text-[11px] font-bold"
              style={{ background: "#ecfdf5", color: "#047857" }}
            >
              Operational Active
            </span>
          </div>
          <p className="text-[13px] text-slate-400 mt-1">
            Live KPIs and operational metrics — ImpactFlow ERP &nbsp;•&nbsp; Last synced 2 mins ago
          </p>
        </div>
        <div className="flex items-center gap-2.5">
          <button className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-slate-200 hover:border-slate-300 text-[#1e2a5e] rounded-lg text-[13px] font-medium shadow-sm transition-colors">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z" />
            </svg>
            Export Snapshot
          </button>
          <button className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-slate-200 hover:border-slate-300 text-[#1e2a5e] rounded-lg text-[13px] font-medium shadow-sm transition-colors">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
              <path d="M3 17v2h6v-2H3zM3 5v2h10V5H3zm10 16v-2h8v-2h-8v-2h-2v6h2zM7 9v2H3v2h4v2h2V9H7zm14 4v-2H11v2h10zm-6-4h2V7h4V5h-4V3h-2v6z" />
            </svg>
            Configure Dashboard
          </button>
        </div>
      </div>

      {/* KPI tiles */}
      {isLoading ? (
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
          {Array.from({ length: 4 }).map((_, i) => (
            <Skeleton key={i} className="h-28 w-full rounded-xl" />
          ))}
        </div>
      ) : tiles.length > 0 ? (
        <StatTiles tiles={tiles} />
      ) : (
        <p className="text-[13px] text-slate-400">No KPIs available for your role yet.</p>
      )}

      {/* Charts grid */}
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
        {statusData.length > 0 && (
          <SectionCard
            title="Programs by Status"
            subtitle="Distribution across mission lifecycles"
          >
            <ResponsiveContainer width="100%" height={220}>
              <BarChart data={statusData} margin={{ top: 8, right: 8, left: -8, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" vertical={false} />
                <XAxis
                  dataKey="status"
                  tick={{ fill: "#94a3b8", fontSize: 11, fontFamily: "Inter" }}
                  axisLine={{ stroke: "#e2e8f0" }}
                  tickLine={false}
                />
                <YAxis
                  allowDecimals={false}
                  tick={{ fill: "#94a3b8", fontSize: 11, fontFamily: "Inter" }}
                  axisLine={false}
                  tickLine={false}
                  width={28}
                />
                <Tooltip contentStyle={tooltipStyle} cursor={{ fill: "#f8fafc" }} />
                <Bar dataKey="count" fill="#4f6bc7" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </SectionCard>
        )}

        {enrollmentData.length > 0 && (
          <SectionCard
            title="Beneficiaries Enrolled by Month"
            subtitle="Cumulative enrollment trend"
          >
            <ResponsiveContainer width="100%" height={220}>
              <AreaChart data={enrollmentData} margin={{ top: 8, right: 8, left: -8, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" vertical={false} />
                <XAxis
                  dataKey="month"
                  tick={{ fill: "#94a3b8", fontSize: 11, fontFamily: "Inter" }}
                  axisLine={{ stroke: "#e2e8f0" }}
                  tickLine={false}
                />
                <YAxis
                  allowDecimals={false}
                  tick={{ fill: "#94a3b8", fontSize: 11, fontFamily: "Inter" }}
                  axisLine={false}
                  tickLine={false}
                  width={28}
                />
                <Tooltip contentStyle={tooltipStyle} cursor={{ stroke: "#e2e8f0" }} />
                <Area
                  type="monotone"
                  dataKey="count"
                  stroke="#2a78d6"
                  fill="#2a78d6"
                  fillOpacity={0.08}
                  strokeWidth={2}
                />
              </AreaChart>
            </ResponsiveContainer>
          </SectionCard>
        )}

        {expensesByCategory.length > 0 && (
          <SectionCard
            title="Approved Expenses by Category"
            subtitle="Top expense categories this period"
            wide
          >
            <ResponsiveContainer
              width="100%"
              height={Math.max(160, expensesByCategory.length * 44)}
            >
              <BarChart
                data={expensesByCategory}
                layout="vertical"
                margin={{ top: 8, right: 16, left: 0, bottom: 0 }}
              >
                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" horizontal={false} />
                <XAxis
                  type="number"
                  tick={{ fill: "#94a3b8", fontSize: 11, fontFamily: "Inter" }}
                  axisLine={{ stroke: "#e2e8f0" }}
                  tickLine={false}
                />
                <YAxis
                  dataKey="category"
                  type="category"
                  width={130}
                  tick={{ fill: "#64748b", fontSize: 11, fontFamily: "Inter" }}
                  axisLine={false}
                  tickLine={false}
                />
                <Tooltip
                  contentStyle={tooltipStyle}
                  cursor={{ fill: "#f8fafc" }}
                  formatter={(value) => formatCurrency(Number(value))}
                />
                <Bar dataKey="amount" fill="#3e59ae" radius={[0, 4, 4, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </SectionCard>
        )}
      </div>
    </div>
  );
}
