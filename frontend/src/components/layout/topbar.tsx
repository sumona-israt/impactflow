"use client";

import { useRouter } from "next/navigation";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { logout } from "@/lib/api/endpoints/auth";
import { roleLabel } from "@/lib/rbac/labels";
import type { AuthUser } from "@/types/auth";
import { NotificationBell } from "./notification-bell";

function initials(name: string): string {
  return name
    .split(" ")
    .map((part) => part[0])
    .slice(0, 2)
    .join("")
    .toUpperCase();
}

export function Topbar({ user }: { user: AuthUser }) {
  const router = useRouter();

  async function handleLogout() {
    await logout();
    router.push("/login");
    router.refresh();
  }

  return (
    <header className="h-14 bg-white border-b border-slate-200 px-4 md:px-6 flex items-center justify-between flex-shrink-0 z-10">
      {/* Left: Search */}
      <div className="flex items-center w-full max-w-lg relative">
        <svg
          className="absolute left-3 text-slate-400 pointer-events-none"
          width="16"
          height="16"
          viewBox="0 0 24 24"
          fill="currentColor"
        >
          <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
        </svg>
        <input
          type="text"
          placeholder="Search programs, grants, beneficiary records..."
          className="w-full h-[34px] pl-9 pr-16 bg-slate-50 border border-slate-200 rounded-lg text-[13px] text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-[#4f6bc7]/30 focus:border-[#4f6bc7] transition-all"
        />
        <div className="absolute right-2.5 flex items-center">
          <kbd className="px-1.5 py-0.5 text-[10px] font-mono bg-white border border-slate-200 rounded text-slate-400 shadow-sm">
            ⌘K
          </kbd>
        </div>
      </div>

      {/* Right: actions */}
      <div className="flex items-center gap-3 ml-4">
        {/* Node status indicator */}
        <div className="hidden lg:flex items-center gap-2 px-2.5 py-1 bg-slate-50 rounded-lg border border-slate-200 text-[12px]">
          <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
          <span className="font-medium text-[#1e2a5e]">Geneva (CHE-01)</span>
        </div>

        {/* Quick actions */}
        <div className="hidden sm:flex items-center gap-2">
          <button className="inline-flex items-center gap-1.5 h-8 px-3 bg-white border border-slate-200 hover:bg-slate-50 text-[#1e2a5e] rounded-lg text-[12px] font-medium transition-colors">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z" />
            </svg>
            Sync
          </button>
          <button className="inline-flex items-center gap-1.5 h-8 px-3 bg-[#1e2a5e] hover:bg-[#2d4a9e] text-white rounded-lg text-[12px] font-medium transition-colors">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
            </svg>
            New Report
          </button>
        </div>

        {/* Notification bell */}
        <NotificationBell />

        {/* Divider */}
        <div className="hidden md:block w-px h-6 bg-slate-200" />

        {/* User dropdown */}
        <DropdownMenu>
          <DropdownMenuTrigger className="flex items-center gap-2 hover:bg-slate-50 rounded-lg px-2 py-1.5 transition-colors focus:outline-none group">
            <Avatar className="size-7">
              <AvatarFallback
                className="text-xs font-bold text-white"
                style={{ background: "#4f6bc7" }}
              >
                {initials(user.name)}
              </AvatarFallback>
            </Avatar>
            <div className="hidden xl:block text-left">
              <div className="text-[13px] font-semibold text-slate-800 group-hover:text-[#1e2a5e] leading-tight">
                {user.name}
              </div>
              <div className="text-[11px] text-slate-400 font-medium">
                {user.roles.map(roleLabel).join(", ") || "No role assigned"}
              </div>
            </div>
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="currentColor"
              className="text-slate-400 hidden xl:block"
            >
              <path d="M7 10l5 5 5-5z" />
            </svg>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="w-60">
            <DropdownMenuLabel>
              <div className="flex flex-col gap-1">
                <span className="text-sm font-semibold text-slate-800">{user.name}</span>
                <span className="text-xs font-normal text-slate-500">{user.email}</span>
                <span className="text-xs font-normal text-slate-500">
                  {user.roles.map(roleLabel).join(", ") || "No role assigned"}
                </span>
              </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onSelect={handleLogout}
              className="text-red-600 focus:text-red-600 focus:bg-red-50"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" className="mr-2">
                <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z" />
              </svg>
              Sign out
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </header>
  );
}
