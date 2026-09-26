"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { hasAnyPermission } from "@/lib/auth/permissions";
import { cn } from "@/lib/utils";
import type { AuthUser } from "@/types/auth";
import { NAV_ITEMS } from "./nav-items";

function ImpactFlowLogo({ size = 28 }: { size?: number }) {
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <circle cx="12" cy="12" r="3" fill="white" />
      <circle cx="4" cy="6" r="2" fill="rgba(255,255,255,0.75)" />
      <circle cx="20" cy="6" r="2" fill="rgba(255,255,255,0.75)" />
      <circle cx="4" cy="18" r="2" fill="rgba(255,255,255,0.75)" />
      <circle cx="20" cy="18" r="2" fill="rgba(255,255,255,0.75)" />
      <line x1="6" y1="6" x2="10" y2="10" stroke="rgba(255,255,255,0.4)" strokeWidth="1.5" />
      <line x1="18" y1="6" x2="14" y2="10" stroke="rgba(255,255,255,0.4)" strokeWidth="1.5" />
      <line x1="6" y1="18" x2="10" y2="14" stroke="rgba(255,255,255,0.4)" strokeWidth="1.5" />
      <line x1="18" y1="18" x2="14" y2="14" stroke="rgba(255,255,255,0.4)" strokeWidth="1.5" />
    </svg>
  );
}

function initials(name: string): string {
  return name
    .split(" ")
    .map((part) => part[0])
    .slice(0, 2)
    .join("")
    .toUpperCase();
}

export function Sidebar({ user }: { user: AuthUser }) {
  const pathname = usePathname();

  const visibleItems = NAV_ITEMS.filter(
    (item) => !item.requiresAnyPermission || hasAnyPermission(user, item.requiresAnyPermission),
  );

  return (
    <aside
      className="hidden w-60 shrink-0 md:flex md:flex-col justify-between border-r shadow-md z-20"
      style={{
        background: "#1e2a5e",
        borderColor: "rgba(255,255,255,0.10)",
      }}
    >
      {/* Brand header */}
      <div>
        <div
          className="p-4 flex items-center gap-3"
          style={{ borderBottom: "1px solid rgba(255,255,255,0.12)" }}
        >
          <div
            className="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
            style={{ background: "rgba(255,255,255,0.1)" }}
          >
            <ImpactFlowLogo size={20} />
          </div>
          <div>
            <div className="text-[15px] font-bold tracking-tight text-white leading-tight">
              ImpactFlow
            </div>
            <div className="text-[10px] font-bold uppercase tracking-widest text-blue-200/70 mt-0.5">
              Enterprise NGO
            </div>
          </div>
        </div>

        {/* Navigation */}
        <nav className="p-3 space-y-0.5">
          {visibleItems.map((item) => {
            const isActive = pathname === item.href || pathname.startsWith(item.href + "/");
            const isDisabled = item.plannedForPhase !== undefined;

            if (isDisabled) {
              return (
                <div
                  key={item.href}
                  className="flex items-center justify-between px-3 py-2 rounded-lg text-[13px]"
                  style={{ color: "rgba(255,255,255,0.35)" }}
                  title={`Planned for Phase ${item.plannedForPhase}`}
                >
                  <span className="flex items-center gap-2.5">
                    <item.icon className="size-[18px]" />
                    {item.label}
                  </span>
                  <span
                    className="rounded px-1.5 py-0.5 text-[10px] font-bold"
                    style={{ background: "rgba(255,255,255,0.06)", color: "rgba(255,255,255,0.4)" }}
                  >
                    Phase {item.plannedForPhase}
                  </span>
                </div>
              );
            }

            // Special badge for Odoo Integration
            const showConnectedBadge = item.href === "/odoo";

            return (
              <Link
                key={item.href}
                href={item.href}
                className={cn(
                  "flex items-center justify-between px-3 py-2 rounded-lg text-[13px] font-medium transition-colors duration-150",
                  isActive
                    ? "bg-[#4f6bc7] text-white font-semibold shadow-sm"
                    : "text-slate-300 hover:text-white hover:bg-white/10",
                )}
              >
                <div className="flex items-center gap-2.5">
                  <item.icon className="size-[18px]" />
                  {item.label}
                </div>
                {isActive && (
                  <span className="w-1.5 h-1.5 rounded-full bg-white opacity-80" />
                )}
                {!isActive && showConnectedBadge && (
                  <span
                    className="text-[10px] px-1.5 py-0.5 rounded font-mono font-semibold"
                    style={{ background: "rgba(16,185,129,0.2)", color: "#6ee7b7" }}
                  >
                    Connected
                  </span>
                )}
              </Link>
            );
          })}
        </nav>
      </div>

      {/* User profile footer */}
      <div
        className="p-3"
        style={{ borderTop: "1px solid rgba(255,255,255,0.12)", background: "rgba(0,0,0,0.15)" }}
      >
        <div className="flex items-center justify-between p-2 rounded-lg hover:bg-white/5 transition-colors">
          <div className="flex items-center gap-2.5 min-w-0">
            <div
              className="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold tracking-wider shrink-0"
              style={{
                background: "#4f6bc7",
                color: "white",
                boxShadow: "0 0 0 2px rgba(255,255,255,0.15)",
              }}
            >
              {initials(user.name)}
            </div>
            <div className="min-w-0 overflow-hidden">
              <div className="text-[13px] font-semibold text-white leading-tight truncate">
                {user.name}
              </div>
              <div className="text-[11px] font-medium truncate" style={{ color: "#b5c4ff" }}>
                {user.email}
              </div>
            </div>
          </div>
          <button
            className="text-slate-400 hover:text-white p-1 rounded hover:bg-white/10 transition-colors shrink-0"
            title="Settings / Sign out"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z" />
            </svg>
          </button>
        </div>
      </div>
    </aside>
  );
}
