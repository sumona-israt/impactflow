"use client";

import { useMutation } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { useRef, useState } from "react";
import { toast } from "sonner";
import { Button, buttonVariants } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { uploadImport } from "@/lib/api/endpoints/data-quality";
import { ApiError } from "@/lib/api/errors";

export function NewImportDialog() {
  const [open, setOpen] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const router = useRouter();

  const mutation = useMutation({
    mutationFn: (file: File) => uploadImport(file, "beneficiaries"),
    onSuccess: (dataImport) => {
      toast.success("File uploaded — map its columns next.");
      setOpen(false);
      router.push(`/data-quality/imports/${dataImport.id}`);
    },
    onError: (error) => toast.error(error instanceof ApiError ? error.message : "Failed to upload the file."),
  });

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger className={buttonVariants({ size: "sm" })}>New import</DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Import beneficiaries</DialogTitle>
          <DialogDescription>Upload a CSV or Excel file. You&apos;ll map its columns next.</DialogDescription>
        </DialogHeader>
        <div className="space-y-1.5 py-4">
          <Label htmlFor="import-file">File</Label>
          <Input id="import-file" ref={fileInputRef} type="file" accept=".csv,.txt,.xlsx" />
        </div>
        <DialogFooter>
          <Button
            disabled={mutation.isPending}
            onClick={() => {
              const file = fileInputRef.current?.files?.[0];
              if (file) mutation.mutate(file);
            }}
          >
            {mutation.isPending ? "Uploading…" : "Upload"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
