declare namespace App.Models {
  type WorkshopBlock = {
    id: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    event_id: number;
    name: string;
    starts_at: string /* Date */;
    ends_at: string /* Date */ | null;
    event?: Event | null;
    sessions?: WorkshopSession[] | null;
    assignments_count?: number;
  };
}
