export interface ProgramsSection {
  active_programs: number;
  total_programs: number;
  status_breakdown: Record<string, number>;
}

export interface BeneficiariesSection {
  total_beneficiaries: number;
  enrolled_by_month: Record<string, number>;
}

export interface StaffingSection {
  active_employees?: number;
  active_volunteers?: number;
}

export interface ActivitiesSection {
  activities_this_month: number;
}

export interface FinanceSection {
  total_budget: number;
  approved_expenses: number;
  budget_utilization_pct: number;
  expenses_by_category: Record<string, number>;
}

export interface DataQualitySection {
  score: number;
  total_beneficiaries: number;
  open_issues: number;
  by_status: Record<string, number>;
}

export interface WorkflowsSection {
  pending_approvals: number;
}

/** Every section is optional — the backend omits it entirely when the caller lacks its permission. */
export interface DashboardKpis {
  programs?: ProgramsSection;
  beneficiaries?: BeneficiariesSection;
  staffing?: StaffingSection;
  activities?: ActivitiesSection;
  finance?: FinanceSection;
  data_quality?: DataQualitySection;
  workflows?: WorkflowsSection;
}
