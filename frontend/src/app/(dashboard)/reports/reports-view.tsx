"use client";

import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { Badge } from "@/components/ui/badge";
import { buttonVariants } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
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
import { listPrograms } from "@/lib/api/endpoints/programs";
import { listReports, reportDownloadUrl, reportRedownloadUrl } from "@/lib/api/endpoints/reports";
import { formatDate, statusLabel } from "@/lib/format";
import type { ReportFormat, ReportType } from "@/types/reports";

const REPORT_TYPE_LABELS: Record<ReportType, string> = {
  "program-performance": "Program Performance",
  beneficiaries: "Beneficiaries",
  financial: "Financial",
  "data-quality": "Data Quality",
};

const FORMAT_LABELS: Record<ReportFormat, string> = {
  csv: "CSV",
  xlsx: "Excel (XLSX)",
  pdf: "PDF",
};

export function ReportsView({
  canProgramPerformance,
  canBeneficiaries,
  canFinancial,
  canDataQuality,
}: {
  canProgramPerformance: boolean;
  canBeneficiaries: boolean;
  canFinancial: boolean;
  canDataQuality: boolean;
}) {
  const availableTypes: ReportType[] = [
    ...(canProgramPerformance ? (["program-performance"] as const) : []),
    ...(canBeneficiaries ? (["beneficiaries"] as const) : []),
    ...(canFinancial ? (["financial"] as const) : []),
    ...(canDataQuality ? (["data-quality"] as const) : []),
  ];

  const [type, setType] = useState<ReportType>(availableTypes[0]);
  const [format, setFormat] = useState<ReportFormat>("csv");
  const [programId, setProgramId] = useState<string>("");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");
  const [historyPage, setHistoryPage] = useState(1);

  const showProgramFilter = type === "program-performance" || type === "beneficiaries" || type === "financial";
  const showDateFilter = type === "financial";

  const programsQuery = useQuery({
    queryKey: ["programs-for-reports"],
    queryFn: () => listPrograms({}),
    enabled: showProgramFilter,
  });

  const historyQuery = useQuery({
    queryKey: ["reports-history", historyPage],
    queryFn: () => listReports(historyPage),
  });

  const downloadUrl = reportDownloadUrl(type, format, {
    program_id: showProgramFilter ? programId : undefined,
    date_from: showDateFilter ? dateFrom : undefined,
    date_to: showDateFilter ? dateTo : undefined,
  });

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-lg font-semibold tracking-tight">Reports</h1>
        <p className="text-sm text-muted-foreground">
          Generate and download program, beneficiary, financial, and data quality reports.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-sm font-medium">Generate a report</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex flex-wrap items-end gap-3">
            <div className="space-y-1">
              <Label>Report type</Label>
              <Select value={type} onValueChange={(v) => setType((v ?? type) as ReportType)}>
                <SelectTrigger className="w-52">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {availableTypes.map((t) => (
                    <SelectItem key={t} value={t}>
                      {REPORT_TYPE_LABELS[t]}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-1">
              <Label>Format</Label>
              <Select value={format} onValueChange={(v) => setFormat((v ?? format) as ReportFormat)}>
                <SelectTrigger className="w-40">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {(Object.keys(FORMAT_LABELS) as ReportFormat[]).map((f) => (
                    <SelectItem key={f} value={f}>
                      {FORMAT_LABELS[f]}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {showProgramFilter && (
              <div className="space-y-1">
                <Label>Program (optional)</Label>
                <Select value={programId || "all"} onValueChange={(v) => setProgramId(v === "all" || !v ? "" : v)}>
                  <SelectTrigger className="w-52">
                    <SelectValue placeholder="All programs" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All programs</SelectItem>
                    {programsQuery.data?.data.map((program) => (
                      <SelectItem key={program.id} value={program.id}>
                        {program.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            )}

            {showDateFilter && (
              <>
                <div className="space-y-1">
                  <Label>From</Label>
                  <Input
                    type="date"
                    className="w-40"
                    value={dateFrom}
                    onChange={(e) => setDateFrom(e.target.value)}
                  />
                </div>
                <div className="space-y-1">
                  <Label>To</Label>
                  <Input type="date" className="w-40" value={dateTo} onChange={(e) => setDateTo(e.target.value)} />
                </div>
              </>
            )}

            <a href={downloadUrl} className={buttonVariants({ variant: "default" })}>
              Generate &amp; download
            </a>
          </div>
        </CardContent>
      </Card>

      <div className="space-y-3">
        <h2 className="text-sm font-medium">Recent reports</h2>
        <div className="rounded-lg border bg-card">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Type</TableHead>
                <TableHead>Format</TableHead>
                <TableHead>Generated by</TableHead>
                <TableHead>Generated at</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {historyQuery.isLoading &&
                Array.from({ length: 4 }).map((_, i) => (
                  <TableRow key={i}>
                    <TableCell colSpan={4}>
                      <Skeleton className="h-6 w-full" />
                    </TableCell>
                  </TableRow>
                ))}
              {historyQuery.data?.data.length === 0 && (
                <TableRow>
                  <TableCell colSpan={4} className="py-6 text-center text-sm text-muted-foreground">
                    No reports generated yet.
                  </TableCell>
                </TableRow>
              )}
              {historyQuery.data?.data.map((report) => (
                <TableRow key={report.id}>
                  <TableCell className="font-medium">
                    <a href={reportRedownloadUrl(report.id)} className="hover:underline">
                      {REPORT_TYPE_LABELS[report.type]}
                    </a>
                  </TableCell>
                  <TableCell>
                    <Badge variant="secondary">{statusLabel(report.format)}</Badge>
                  </TableCell>
                  <TableCell className="text-muted-foreground">{report.generator?.name ?? "—"}</TableCell>
                  <TableCell className="text-muted-foreground">{formatDate(report.generated_at)}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
        {historyQuery.data && <PaginationControls meta={historyQuery.data.meta} onPageChange={setHistoryPage} />}
      </div>
    </div>
  );
}
