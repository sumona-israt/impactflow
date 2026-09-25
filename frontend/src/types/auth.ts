export interface AuthUser {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  roles: string[];
  permissions: string[];
  last_login_at: string | null;
}
