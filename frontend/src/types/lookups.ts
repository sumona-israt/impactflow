export interface Department {
  id: number;
  name: string;
  parent_department_id: number | null;
}

export interface Branch {
  id: number;
  name: string;
  district: string | null;
  upazila: string | null;
  address: string | null;
}

export interface ProgramCategory {
  id: number;
  name: string;
  description: string | null;
}
