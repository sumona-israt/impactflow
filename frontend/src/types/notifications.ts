export interface NotificationEntry {
  id: string;
  type: string;
  data: {
    message: string;
    entity_type?: string;
    entity_id?: string;
  };
  read_at: string | null;
  created_at: string;
}
