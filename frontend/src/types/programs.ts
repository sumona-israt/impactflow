export type ProgramStatus =
  | "draft"
  | "pending_approval"
  | "approved"
  | "active"
  | "paused"
  | "completed"
  | "archived";

export interface Program {
  id: string;
  name: string;
  description: string | null;
  category: { id: number; name: string } | null;
  manager: { id: number; name: string } | null;
  branch: { id: number; name: string } | null;
  district: string | null;
  upazila: string | null;
  start_date: string | null;
  end_date: string | null;
  status: ProgramStatus;
  budget: string | null;
  target_beneficiaries: number | null;
  actual_beneficiaries: number;
  progress: number;
  created_at: string;
}

export interface Enrollment {
  id: number;
  status: "enrolled" | "completed" | "withdrawn";
  enrolled_at: string;
  beneficiary?: { id: string; full_name: string; district: string | null; upazila: string | null; status: string; registration_date: string };
  program?: { id: string; name: string };
}
