declare namespace App.Models {
  type Event = {
    sort_order: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    consider_alcohol: any; // NOT FOUND;
    form: any | null; // NOT FOUND;
    id: number;
    registration_from: string /* Date */;
    registration_to: string /* Date */;
    has_requirements: any; // NOT FOUND;
    name: string;
    description: string | null;
    type: string;
    groups?: Group[] | null;
    registrations?: Registration[] | null;
    slots?: Slot[] | null;
    stations?: Station[] | null;
  };
}
