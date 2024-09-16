declare namespace App.Models {
  type Slot = {
    id: number;
    maximum_participants: number | null;
    form: any | null; // NOT FOUND;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    event_id: number;
    has_requirements: any; // NOT FOUND;
    name: string;
    telegram_group_link: string | null;
    registrations?: Registration[] | null;
    event?: Event | null;
  };
}
