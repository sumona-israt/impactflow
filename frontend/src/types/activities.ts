export type ActivityStatus = "scheduled" | "completed" | "cancelled";

export interface Activity {
  id: string;
  program_id: string;
  title: string;
  description: string | null;
  scheduled_at: string;
  location: string | null;
  status: ActivityStatus;
  attendance_count?: number;
}

export interface AttendanceRecord {
  beneficiary_id: string;
  beneficiary_name?: string;
  attended: boolean;
}
