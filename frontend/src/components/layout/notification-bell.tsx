"use client";

import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Bell } from "lucide-react";
import Link from "next/link";
import { Badge } from "@/components/ui/badge";
import { buttonVariants } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  getUnreadNotificationCount,
  listNotifications,
  markAllNotificationsRead,
  markNotificationRead,
} from "@/lib/api/endpoints/notifications";
import { formatDate } from "@/lib/format";

export function NotificationBell() {
  const queryClient = useQueryClient();

  const unreadQuery = useQuery({
    queryKey: ["notifications-unread-count"],
    queryFn: getUnreadNotificationCount,
    refetchInterval: 30000,
  });

  const recentQuery = useQuery({
    queryKey: ["notifications-recent"],
    queryFn: () => listNotifications(1),
  });

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ["notifications-unread-count"] });
    queryClient.invalidateQueries({ queryKey: ["notifications-recent"] });
  };

  const unreadCount = unreadQuery.data ?? 0;
  const recent = recentQuery.data?.data.slice(0, 5) ?? [];

  return (
    <DropdownMenu>
      <DropdownMenuTrigger className={buttonVariants({ variant: "ghost", className: "relative px-2" })}>
        <Bell className="size-4" />
        {unreadCount > 0 && (
          <Badge
            variant="destructive"
            className="absolute -top-1 -right-1 h-4 min-w-4 justify-center rounded-full px-1 text-[10px]"
          >
            {unreadCount > 9 ? "9+" : unreadCount}
          </Badge>
        )}
      </DropdownMenuTrigger>
      <DropdownMenuContent align="start" className="w-80">
        <DropdownMenuLabel className="flex items-center justify-between">
          <span>Notifications</span>
          {unreadCount > 0 && (
            <button
              type="button"
              className="text-xs font-normal text-primary hover:underline"
              onClick={async () => {
                await markAllNotificationsRead();
                invalidate();
              }}
            >
              Mark all read
            </button>
          )}
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        {recent.length === 0 && (
          <div className="px-2 py-4 text-center text-sm text-muted-foreground">No notifications yet.</div>
        )}
        {recent.map((notification) => (
          <DropdownMenuItem
            key={notification.id}
            className="flex flex-col items-start gap-0.5 whitespace-normal"
            onSelect={async () => {
              if (!notification.read_at) {
                await markNotificationRead(notification.id);
                invalidate();
              }
            }}
          >
            <span className={notification.read_at ? "text-muted-foreground" : "font-medium"}>
              {notification.data.message}
            </span>
            <span className="text-xs text-muted-foreground">{formatDate(notification.created_at)}</span>
          </DropdownMenuItem>
        ))}
        <DropdownMenuSeparator />
        <DropdownMenuItem render={<Link href="/notifications">View all notifications</Link>} />
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
