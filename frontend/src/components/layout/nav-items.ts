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
}

export const NAV_ITEMS: NavItem[] = [
  { label: "Dashboard", href: "/dashboard", icon: LayoutDashboard },
  { label: "Programs", href: "/programs", icon: ClipboardList, plannedForPhase: 3 },
  { label: "Beneficiaries", href: "/beneficiaries", icon: Users, plannedForPhase: 3 },
  { label: "Staff & Volunteers", href: "/people", icon: UserRound, plannedForPhase: 3 },
  { label: "Finance & Assets", href: "/finance", icon: Banknote, plannedForPhase: 4 },
  { label: "Data Quality", href: "/data-quality", icon: Database, plannedForPhase: 5 },
  { label: "Odoo Integration", href: "/odoo", icon: Plug, plannedForPhase: 7 },
  { label: "Administration", href: "/admin", icon: ShieldCheck, plannedForPhase: 2 },
];
