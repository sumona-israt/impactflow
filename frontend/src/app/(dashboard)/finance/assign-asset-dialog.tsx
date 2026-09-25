"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { assignAsset } from "@/lib/api/endpoints/assets";
import { ApiError } from "@/lib/api/errors";
import type { Asset } from "@/types/finance";

/**
 * HR/Admin Officer (who assigns assets) doesn't hold users.viewAny, so a
 * searchable user picker isn't available here — see docs/database-design.md
 * §7 area notes. A numeric account ID is a real, if unpolished, stand-in
 * rather than a picker that would need permissions this role doesn't have.
 */
export function AssignAssetDialog({
  asset,
  open,
  onOpenChange,
}: {
  asset: Asset;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const [userId, setUserId] = useState("");
  const queryClient = useQueryClient();

  const mutation = useMutation({
    mutationFn: () => assignAsset(asset.id, Number(userId)),
    onSuccess: () => {
      toast.success("Asset assigned.");
      queryClient.invalidateQueries({ queryKey: ["assets"] });
      onOpenChange(false);
    },
    onError: (error) => toast.error(error instanceof ApiError ? error.message : "Failed to assign asset."),
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>Assign — {asset.name}</DialogTitle>
        </DialogHeader>
        <div className="space-y-1.5 py-2">
          <Label htmlFor="assign-user-id">User account ID</Label>
          <Input
            id="assign-user-id"
            type="number"
            value={userId}
            onChange={(e) => setUserId(e.target.value)}
            placeholder="e.g. 4"
          />
          <p className="text-xs text-muted-foreground">
            Find this on the user&apos;s profile in Administration → Users, if you have access.
          </p>
        </div>
        <DialogFooter>
          <Button onClick={() => mutation.mutate()} disabled={!userId || mutation.isPending}>
            {mutation.isPending ? "Assigning…" : "Assign"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
