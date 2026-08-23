declare namespace App.Models {
  type Slot = {
    id: number;
    name: string;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    event_id: number;
    has_requirements: boolean;
    maximum_participants: number | null;
    form: any | null; // NOT FOUND;
    telegram_group_link: string | null;
    registrations?: Registration[] | null;
    event?: Event | null;
    use_factory?: any | null;
  };
}
