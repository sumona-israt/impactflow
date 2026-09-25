import type { LucideIcon } from "lucide-react";
import {
  Banknote,
  ClipboardList,
  Database,
  LayoutDashboard,
  Plug,
  ShieldCheck,
  UserRound,
  Users,
} from "lucide-react";

export interface NavItem {
  label: string;
  href: string;
  icon: LucideIcon;
  /** Undefined means the route is live today. */
  plannedForPhase?: number;
  /** Item is hidden entirely unless the user has at least one of these permissions. */
  requiresAnyPermission?: string[];
}

export const NAV_ITEMS: NavItem[] = [
  { label: "Dashboard", href: "/dashboard", icon: LayoutDashboard },
  {
    label: "Programs",
    href: "/programs",
    icon: ClipboardList,
    requiresAnyPermission: ["programs.viewAny"],
  },
  {
    label: "Beneficiaries",
    href: "/beneficiaries",
    icon: Users,
    requiresAnyPermission: ["beneficiaries.viewAny"],
  },
  {
    label: "Staff & Volunteers",
    href: "/people",
    icon: UserRound,
    requiresAnyPermission: ["employees.viewAny", "volunteers.viewAny"],
  },
  {
    label: "Finance & Assets",
    href: "/finance",
    icon: Banknote,
    requiresAnyPermission: ["expenses.viewAny", "assets.viewAny"],
  },
  { label: "Data Quality", href: "/data-quality", icon: Database, plannedForPhase: 5 },
  { label: "Odoo Integration", href: "/odoo", icon: Plug, plannedForPhase: 7 },
  {
    label: "Administration",
    href: "/admin/users",
    icon: ShieldCheck,
    requiresAnyPermission: ["users.viewAny", "roles.viewAny", "audit-logs.viewAny"],
  },
];
