"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { hasAnyPermission } from "@/lib/auth/permissions";
import { cn } from "@/lib/utils";
import type { AuthUser } from "@/types/auth";
import { NAV_ITEMS } from "./nav-items";

export function Sidebar({ user }: { user: AuthUser }) {
  const pathname = usePathname();

  const visibleItems = NAV_ITEMS.filter(
    (item) => !item.requiresAnyPermission || hasAnyPermission(user, item.requiresAnyPermission),
  );

  return (
    <aside className="hidden w-64 shrink-0 border-r bg-sidebar md:flex md:flex-col">
      <div className="flex h-14 items-center border-b px-4">
        <span className="text-sm font-semibold tracking-tight text-sidebar-foreground">
          ImpactFlow
        </span>
      </div>
      <nav className="flex-1 space-y-1 p-3">
        {visibleItems.map((item) => {
          const isActive = pathname.startsWith(item.href);
          const isDisabled = item.plannedForPhase !== undefined;

          if (isDisabled) {
            return (
              <div
                key={item.href}
                className="flex items-center justify-between rounded-md px-3 py-2 text-sm text-muted-foreground/60"
                title={`Planned for Phase ${item.plannedForPhase}`}
              >
                <span className="flex items-center gap-2">
                  <item.icon className="size-4" />
                  {item.label}
                </span>
                <span className="rounded bg-muted px-1.5 py-0.5 text-[10px] font-medium">
                  Phase {item.plannedForPhase}
                </span>
              </div>
            );
          }

          return (
            <Link
              key={item.href}
              href={item.href}
              className={cn(
                "flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors",
                isActive
                  ? "bg-sidebar-primary text-sidebar-primary-foreground"
                  : "text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground",
              )}
            >
              <item.icon className="size-4" />
              {item.label}
            </Link>
          );
        })}
      </nav>
    </aside>
  );
}
