export type BeneficiaryStatus = "active" | "inactive" | "graduated";

/** List view — sensitive fields deliberately excluded server-side. */
export interface BeneficiaryListItem {
  id: string;
  full_name: string;
  district: string | null;
  upazila: string | null;
  status: BeneficiaryStatus;
  registration_date: string;
}

/** Detail view — full record, only returned to authorized callers. */
export interface Beneficiary {
  id: string;
  full_name: string;
  date_of_birth: string | null;
  gender: string | null;
  phone: string | null;
  email: string | null;
  address: string | null;
  district: string | null;
  upazila: string | null;
  status: BeneficiaryStatus;
  registration_date: string;
  emergency_contact_name: string | null;
  emergency_contact_phone: string | null;
  notes: string | null;
  created_at: string;
}
