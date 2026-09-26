export interface OdooEntitySyncCounts {
  synced: number;
  failed: number;
  last_success_at: string | null;
}

export interface OdooStatus {
  mode: "mock" | "live";
  is_active: boolean;
  connected: boolean;
  entities: Record<string, OdooEntitySyncCounts>;
}

export type OdooSyncStatus = "pending" | "success" | "failed" | "skipped";

export interface OdooSyncLogEntry {
  id: number;
  entity_type: string;
  local_id: string;
  odoo_id: number | null;
  operation: string;
  status: OdooSyncStatus;
  error_message: string | null;
  retry_count: number;
  request_time: string | null;
  response_time: string | null;
}

export interface OdooConfig {
  mode: "mock" | "live";
  base_url: string | null;
  database: string | null;
  is_active: boolean;
}
