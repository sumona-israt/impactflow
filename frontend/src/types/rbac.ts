export interface Role {
  id: number;
  name: string;
  permissions: string[];
}

export interface Permission {
  id: number;
  name: string;
}

export interface ManagedUser {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  roles: string[];
  permissions: string[];
  is_active: boolean;
  last_login_at: string | null;
  created_at: string;
}

export interface AuditLogEntry {
  id: number;
  action: string;
  entity_type: string;
  entity_id: number | null;
  old_values: Record<string, unknown> | null;
  new_values: Record<string, unknown> | null;
  ip_address: string | null;
  user_agent: string | null;
  created_at: string;
  user: { id: number; name: string; email: string } | null;
}

export interface PageMeta {
  page: number;
  per_page: number;
  total: number;
}
