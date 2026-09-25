export type EmployeeStatus = "active" | "on_leave" | "terminated";
export type VolunteerStatus = "active" | "inactive";
export type VolunteerAvailability = "weekdays" | "weekends" | "evenings" | "full_time";

export interface Employee {
  id: string;
  name: string;
  user_id: number | null;
  department: { id: number; name: string } | null;
  branch: { id: number; name: string } | null;
  position: string | null;
  joining_date: string | null;
  status: EmployeeStatus;
}

export interface Volunteer {
  id: string;
  full_name: string;
  phone: string | null;
  email: string | null;
  skills: string[];
  availability: VolunteerAvailability | null;
  status: VolunteerStatus;
}
