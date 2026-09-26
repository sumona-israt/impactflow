export type ReportType = "program-performance" | "beneficiaries" | "financial" | "data-quality";
export type ReportFormat = "csv" | "xlsx" | "pdf";

export interface ReportHistoryEntry {
  id: string;
  type: ReportType;
  format: ReportFormat;
  parameters: Record<string, unknown> | null;
  generator: { id: number; name: string } | null;
  generated_at: string;
}
