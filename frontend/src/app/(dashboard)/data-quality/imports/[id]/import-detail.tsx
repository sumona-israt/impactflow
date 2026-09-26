"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
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
  commitImport,
  getImport,
  getImportPreviewRows,
  runImportPreview,
  updateImportMapping,
} from "@/lib/api/endpoints/data-quality";
import { ApiError } from "@/lib/api/errors";
import { statusLabel } from "@/lib/format";
import { IMPORT_MAPPABLE_FIELDS } from "@/types/data-quality";

export function ImportDetail({ importId, canCreateImport }: { importId: string; canCreateImport: boolean }) {
  const queryClient = useQueryClient();
  // Only the viewer's not-yet-saved edits — merged with the server's saved
  // column_mapping below, so there's no effect syncing server data into
  // local state (and no risk of clobbering an in-progress edit on refetch).
  const [mappingEdits, setMappingEdits] = useState<Record<string, string>>({});
  const [previewPage, setPreviewPage] = useState(1);

  const { data: dataImport, isLoading, isError } = useQuery({
    queryKey: ["data-imports", importId],
    queryFn: () => getImport(importId),
    refetchInterval: (query) => (query.state.data?.status === "committing" ? 2000 : false),
  });

  const mapping = { ...(dataImport?.column_mapping ?? {}), ...mappingEdits };

  const rowsQuery = useQuery({
    queryKey: ["data-imports", importId, "preview", previewPage],
    queryFn: () => getImportPreviewRows(importId, previewPage),
    enabled: dataImport?.status === "previewed",
  });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ["data-imports", importId] });

  const mappingMutation = useMutation({
    mutationFn: () => updateImportMapping(importId, mapping),
    onSuccess: () => {
      toast.success("Column mapping saved.");
      invalidate();
    },
    onError: (error) => toast.error(error instanceof ApiError ? error.message : "Failed to save mapping."),
  });

  const previewMutation = useMutation({
    mutationFn: () => runImportPreview(importId),
    onSuccess: () => {
      toast.success("Preview generated.");
      invalidate();
    },
    onError: (error) => toast.error(error instanceof ApiError ? error.message : "Failed to generate preview."),
  });

  const commitMutation = useMutation({
    mutationFn: () => commitImport(importId),
    onSuccess: () => {
      toast.success("Import committed — beneficiaries are being created.");
      invalidate();
    },
    onError: (error) => toast.error(error instanceof ApiError ? error.message : "Failed to commit import."),
  });

  if (isLoading) return <Skeleton className="h-64 w-full" />;
  if (isError || !dataImport) return <p className="text-sm text-destructive">Failed to load this import.</p>;

  const canRunPreview = IMPORT_MAPPABLE_FIELDS.filter((f) => f.required).every((f) => mapping[f.field]);

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between">
        <div>
          <h1 className="text-lg font-semibold tracking-tight">{dataImport.original_name}</h1>
          <p className="text-sm text-muted-foreground">Uploaded by {dataImport.uploader.name}</p>
        </div>
        <Badge variant="secondary">{statusLabel(dataImport.status)}</Badge>
      </div>

      {dataImport.status === "failed" && dataImport.error_message && (
        <Card>
          <CardHeader><CardTitle className="text-sm font-medium text-destructive">Import failed</CardTitle></CardHeader>
          <CardContent className="text-sm text-muted-foreground">{dataImport.error_message}</CardContent>
        </Card>
      )}

      {canCreateImport && (dataImport.status === "uploaded" || dataImport.status === "mapped" || dataImport.status === "previewed") && (
        <Card>
          <CardHeader><CardTitle className="text-sm font-medium">Map columns</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              {IMPORT_MAPPABLE_FIELDS.map(({ field, label, required }) => (
                <div key={field} className="space-y-1.5">
                  <label className="text-sm font-medium">
                    {label} {required && <span className="text-destructive">*</span>}
                  </label>
                  <Select
                    value={mapping[field] ?? ""}
                    onValueChange={(value) => setMappingEdits((m) => ({ ...m, [field]: value ?? "" }))}
                  >
                    <SelectTrigger className="w-full"><SelectValue placeholder="Not mapped" /></SelectTrigger>
                    <SelectContent>
                      {dataImport.detected_headers.map((header) => (
                        <SelectItem key={header} value={header}>{header}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              ))}
            </div>
            <div className="flex gap-2 pt-2">
              <Button
                variant="outline"
                disabled={mappingMutation.isPending}
                onClick={() => mappingMutation.mutate()}
              >
                {mappingMutation.isPending ? "Saving…" : "Save mapping"}
              </Button>
              <Button
                disabled={!canRunPreview || previewMutation.isPending || dataImport.status === "uploaded"}
                onClick={() => previewMutation.mutate()}
              >
                {previewMutation.isPending ? "Generating…" : "Run preview"}
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {dataImport.status === "previewed" && (
        <>
          <div className="grid grid-cols-3 gap-4">
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-sm font-medium text-muted-foreground">Valid</CardTitle></CardHeader>
              <CardContent><p className="text-xl font-semibold">{dataImport.valid_rows}</p></CardContent>
            </Card>
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-sm font-medium text-muted-foreground">Duplicate</CardTitle></CardHeader>
              <CardContent><p className="text-xl font-semibold">{dataImport.duplicate_rows}</p></CardContent>
            </Card>
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-sm font-medium text-muted-foreground">Invalid</CardTitle></CardHeader>
              <CardContent><p className="text-xl font-semibold">{dataImport.invalid_rows}</p></CardContent>
            </Card>
          </div>

          <div className="rounded-lg border bg-card">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Row</TableHead>
                  <TableHead>Name</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Errors</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rowsQuery.isLoading &&
                  Array.from({ length: 4 }).map((_, i) => (
                    <TableRow key={i}><TableCell colSpan={4}><Skeleton className="h-6 w-full" /></TableCell></TableRow>
                  ))}
                {rowsQuery.data?.data.map((row) => (
                  <TableRow key={row.id}>
                    <TableCell>{row.row_number}</TableCell>
                    <TableCell>{String(row.raw_data.full_name ?? "—")}</TableCell>
                    <TableCell><Badge variant="secondary">{statusLabel(row.status)}</Badge></TableCell>
                    <TableCell className="text-muted-foreground">
                      {row.errors ? Object.values(row.errors).flat().join(" ") : "—"}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
          {rowsQuery.data && <PaginationControls meta={rowsQuery.data.meta} onPageChange={setPreviewPage} />}

          {canCreateImport && (
            <Button disabled={commitMutation.isPending} onClick={() => commitMutation.mutate()}>
              {commitMutation.isPending ? "Committing…" : `Commit (${(dataImport.valid_rows ?? 0) + (dataImport.duplicate_rows ?? 0)} beneficiaries)`}
            </Button>
          )}
        </>
      )}

      {dataImport.status === "committing" && (
        <p className="text-sm text-muted-foreground">Creating beneficiaries in the background…</p>
      )}

      {dataImport.status === "committed" && (
        <p className="text-sm text-muted-foreground">
          Import committed — {(dataImport.valid_rows ?? 0) + (dataImport.duplicate_rows ?? 0)} beneficiaries created.
        </p>
      )}
    </div>
  );
}
