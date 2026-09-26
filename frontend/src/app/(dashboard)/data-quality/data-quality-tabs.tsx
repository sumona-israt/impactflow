"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import Link from "next/link";
import { useState } from "react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
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
  getDataQualityScore,
  ignoreDataQualityIssue,
  listDataQualityIssues,
  listImports,
  resolveDataQualityIssue,
} from "@/lib/api/endpoints/data-quality";
import { ApiError } from "@/lib/api/errors";
import { formatDate, statusLabel } from "@/lib/format";
import { NewImportDialog } from "./new-import-dialog";

export function DataQualityTabs({
  canViewImports,
  canCreateImport,
  canViewIssues,
  canManageIssues,
}: {
  canViewImports: boolean;
  canCreateImport: boolean;
  canViewIssues: boolean;
  canManageIssues: boolean;
}) {
  const [importsPage, setImportsPage] = useState(1);
  const [issuesPage, setIssuesPage] = useState(1);
  const queryClient = useQueryClient();

  const importsQuery = useQuery({
    queryKey: ["data-imports", importsPage],
    queryFn: () => listImports(importsPage),
    enabled: canViewImports,
  });
  const issuesQuery = useQuery({
    queryKey: ["data-quality-issues", "open", issuesPage],
    queryFn: () => listDataQualityIssues("open", issuesPage),
    enabled: canViewIssues,
  });
  const scoreQuery = useQuery({
    queryKey: ["data-quality-score"],
    queryFn: getDataQualityScore,
    enabled: canViewIssues,
  });

  const invalidateIssues = () => {
    queryClient.invalidateQueries({ queryKey: ["data-quality-issues"] });
    queryClient.invalidateQueries({ queryKey: ["data-quality-score"] });
  };

  const resolveMutation = useMutation({
    mutationFn: resolveDataQualityIssue,
    onSuccess: () => {
      toast.success("Issue resolved.");
      invalidateIssues();
    },
    onError: (error) => toast.error(error instanceof ApiError ? error.message : "Failed to resolve issue."),
  });

  const ignoreMutation = useMutation({
    mutationFn: ignoreDataQualityIssue,
    onSuccess: () => {
      toast.success("Issue ignored.");
      invalidateIssues();
    },
    onError: (error) => toast.error(error instanceof ApiError ? error.message : "Failed to ignore issue."),
  });

  const defaultTab = canViewImports ? "imports" : "issues";

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-lg font-semibold tracking-tight">Data Quality</h1>
        <p className="text-sm text-muted-foreground">Bulk imports, duplicate detection, and data quality issues.</p>
      </div>

      <Tabs defaultValue={defaultTab}>
        <TabsList>
          {canViewImports && <TabsTrigger value="imports">Imports</TabsTrigger>}
          {canViewIssues && <TabsTrigger value="issues">Quality Issues</TabsTrigger>}
        </TabsList>

        {canViewImports && (
          <TabsContent value="imports" className="space-y-3 pt-4">
            {canCreateImport && <NewImportDialog />}
            <div className="rounded-lg border bg-card">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>File</TableHead>
                    <TableHead>Uploaded by</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Valid / Duplicate / Invalid</TableHead>
                    <TableHead>Date</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {importsQuery.isLoading &&
                    Array.from({ length: 4 }).map((_, i) => (
                      <TableRow key={i}><TableCell colSpan={5}><Skeleton className="h-6 w-full" /></TableCell></TableRow>
                    ))}
                  {importsQuery.data?.data.length === 0 && (
                    <TableRow><TableCell colSpan={5} className="py-6 text-center text-sm text-muted-foreground">No imports yet.</TableCell></TableRow>
                  )}
                  {importsQuery.data?.data.map((dataImport) => (
                    <TableRow key={dataImport.id}>
                      <TableCell className="font-medium">
                        <Link href={`/data-quality/imports/${dataImport.id}`} className="hover:underline">
                          {dataImport.original_name}
                        </Link>
                      </TableCell>
                      <TableCell className="text-muted-foreground">{dataImport.uploader.name}</TableCell>
                      <TableCell><Badge variant="secondary">{statusLabel(dataImport.status)}</Badge></TableCell>
                      <TableCell className="text-muted-foreground">
                        {dataImport.valid_rows ?? "—"} / {dataImport.duplicate_rows ?? "—"} / {dataImport.invalid_rows ?? "—"}
                      </TableCell>
                      <TableCell className="text-muted-foreground">{formatDate(dataImport.created_at)}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
            {importsQuery.data && <PaginationControls meta={importsQuery.data.meta} onPageChange={setImportsPage} />}
          </TabsContent>
        )}

        {canViewIssues && (
          <TabsContent value="issues" className="space-y-3 pt-4">
            <Card className="max-w-xs">
              <CardHeader className="pb-2"><CardTitle className="text-sm font-medium text-muted-foreground">Data quality score</CardTitle></CardHeader>
              <CardContent>
                {scoreQuery.isLoading ? (
                  <Skeleton className="h-8 w-24" />
                ) : (
                  <>
                    <p className="text-2xl font-semibold">{scoreQuery.data?.score ?? "—"}%</p>
                    <p className="text-xs text-muted-foreground">
                      {scoreQuery.data?.open_issues ?? 0} open issue(s) across {scoreQuery.data?.total_beneficiaries ?? 0} beneficiaries
                    </p>
                  </>
                )}
              </CardContent>
            </Card>

            <div className="rounded-lg border bg-card">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Entity</TableHead>
                    <TableHead>Issue</TableHead>
                    <TableHead>Description</TableHead>
                    <TableHead>Detected</TableHead>
                    {canManageIssues && <TableHead className="text-right">Actions</TableHead>}
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {issuesQuery.isLoading &&
                    Array.from({ length: 4 }).map((_, i) => (
                      <TableRow key={i}><TableCell colSpan={5}><Skeleton className="h-6 w-full" /></TableCell></TableRow>
                    ))}
                  {issuesQuery.data?.data.length === 0 && (
                    <TableRow><TableCell colSpan={5} className="py-6 text-center text-sm text-muted-foreground">No open issues.</TableCell></TableRow>
                  )}
                  {issuesQuery.data?.data.map((issue) => (
                    <TableRow key={issue.id}>
                      <TableCell className="font-medium">{issue.entity?.name ?? "—"}</TableCell>
                      <TableCell><Badge variant="secondary">{statusLabel(issue.issue_type)}</Badge></TableCell>
                      <TableCell className="text-muted-foreground">{issue.description}</TableCell>
                      <TableCell className="text-muted-foreground">{formatDate(issue.detected_at)}</TableCell>
                      {canManageIssues && (
                        <TableCell className="text-right">
                          <div className="flex justify-end gap-2">
                            <Button
                              variant="outline"
                              size="sm"
                              disabled={resolveMutation.isPending}
                              onClick={() => resolveMutation.mutate(issue.id)}
                            >
                              Resolve
                            </Button>
                            <Button
                              variant="outline"
                              size="sm"
                              disabled={ignoreMutation.isPending}
                              onClick={() => ignoreMutation.mutate(issue.id)}
                            >
                              Ignore
                            </Button>
                          </div>
                        </TableCell>
                      )}
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
            {issuesQuery.data && <PaginationControls meta={issuesQuery.data.meta} onPageChange={setIssuesPage} />}
          </TabsContent>
        )}
      </Tabs>
    </div>
  );
}
