export type ImportEntityType = "beneficiaries";
export type DataImportStatus = "uploaded" | "mapped" | "previewed" | "committing" | "committed" | "failed";
export type DataImportRowStatus = "valid" | "invalid" | "duplicate";
export type DataQualityIssueStatus = "open" | "resolved" | "ignored";

export interface DataImport {
  id: string;
  entity_type: ImportEntityType;
  original_name: string;
  uploader: { id: number; name: string };
  status: DataImportStatus;
  detected_headers: string[];
  column_mapping: Record<string, string> | null;
  total_rows: number | null;
  valid_rows: number | null;
  duplicate_rows: number | null;
  invalid_rows: number | null;
  error_message: string | null;
  created_at: string;
}

export interface DataImportRow {
  id: string;
  row_number: number;
  raw_data: Record<string, unknown>;
  status: DataImportRowStatus;
  errors: Record<string, string[]> | null;
  beneficiary_id: string | null;
}

export interface DataQualityIssue {
  id: string;
  entity_type: string;
  entity_id: string;
  entity: { id: string; name: string | null } | null;
  issue_type: string;
  severity: string;
  description: string;
  status: DataQualityIssueStatus;
  detected_at: string;
  resolved_at: string | null;
  resolver: { id: number; name: string } | null;
}

export interface DataQualityScore {
  score: number;
  total_beneficiaries: number;
  open_issues: number;
}

/** Beneficiary fields an import can map a source column onto. */
export const IMPORT_MAPPABLE_FIELDS = [
  { field: "full_name", label: "Full name", required: true },
  { field: "registration_date", label: "Registration date", required: true },
  { field: "date_of_birth", label: "Date of birth", required: false },
  { field: "gender", label: "Gender", required: false },
  { field: "phone", label: "Phone", required: false },
  { field: "email", label: "Email", required: false },
  { field: "address", label: "Address", required: false },
  { field: "district", label: "District", required: false },
  { field: "upazila", label: "Upazila", required: false },
  { field: "status", label: "Status", required: false },
  { field: "emergency_contact_name", label: "Emergency contact name", required: false },
  { field: "emergency_contact_phone", label: "Emergency contact phone", required: false },
  { field: "notes", label: "Notes", required: false },
] as const;
