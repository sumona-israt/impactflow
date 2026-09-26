"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { PaginationControls } from "@/components/shared/pagination-controls";
import {
  listNotifications,
  markAllNotificationsRead,
  markNotificationRead,
} from "@/lib/api/endpoints/notifications";
import { formatDate } from "@/lib/format";

export function NotificationsView() {
  const [page, setPage] = useState(1);
  const queryClient = useQueryClient();

  const notificationsQuery = useQuery({
    queryKey: ["notifications", page],
    queryFn: () => listNotifications(page),
  });

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ["notifications"] });
    queryClient.invalidateQueries({ queryKey: ["notifications-unread-count"] });
    queryClient.invalidateQueries({ queryKey: ["notifications-recent"] });
  };

  const markReadMutation = useMutation({
    mutationFn: markNotificationRead,
    onSuccess: invalidate,
  });

  const markAllReadMutation = useMutation({
    mutationFn: markAllNotificationsRead,
    onSuccess: invalidate,
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-lg font-semibold tracking-tight">Notifications</h1>
          <p className="text-sm text-muted-foreground">Workflow alerts and sync updates addressed to you.</p>
        </div>
        <Button
          variant="outline"
          size="sm"
          disabled={markAllReadMutation.isPending}
          onClick={() => markAllReadMutation.mutate()}
        >
          Mark all read
        </Button>
      </div>

      <div className="rounded-lg border bg-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Message</TableHead>
              <TableHead>Received</TableHead>
              <TableHead className="text-right">Status</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {notificationsQuery.isLoading &&
              Array.from({ length: 4 }).map((_, i) => (
                <TableRow key={i}>
                  <TableCell colSpan={3}>
                    <Skeleton className="h-6 w-full" />
                  </TableCell>
                </TableRow>
              ))}
            {notificationsQuery.data?.data.length === 0 && (
              <TableRow>
                <TableCell colSpan={3} className="py-6 text-center text-sm text-muted-foreground">
                  No notifications yet.
                </TableCell>
              </TableRow>
            )}
            {notificationsQuery.data?.data.map((notification) => (
              <TableRow key={notification.id}>
                <TableCell className={notification.read_at ? "text-muted-foreground" : "font-medium"}>
                  {notification.data.message}
                </TableCell>
                <TableCell className="text-muted-foreground">{formatDate(notification.created_at)}</TableCell>
                <TableCell className="text-right">
                  {notification.read_at ? (
                    <Badge variant="secondary">Read</Badge>
                  ) : (
                    <Button
                      variant="outline"
                      size="sm"
                      disabled={markReadMutation.isPending}
                      onClick={() => markReadMutation.mutate(notification.id)}
                    >
                      Mark read
                    </Button>
                  )}
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
      {notificationsQuery.data && (
        <PaginationControls meta={notificationsQuery.data.meta} onPageChange={setPage} />
      )}
    </div>
  );
}
