declare namespace App.Models {
  type Workshop = {
    id: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    event_id: number;
    name: string;
    description: string | null;
    event?: Event | null;
    sessions?: WorkshopSession[] | null;
    priorities?: WorkshopPriority[] | null;
    priorities_count?: number;
  };
}
