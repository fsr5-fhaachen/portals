declare namespace App.Models {
  type Event = {
    id: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    name: string;
    description: string | null;
    registration_from: string /* Date */;
    registration_to: string /* Date */;
    type: string;
    has_requirements: boolean;
    consider_alcohol: boolean;
    form: any | null; // NOT FOUND;
    sort_order: number;
    groups?: Group[] | null;
    registrations?: Registration[] | null;
    slots?: Slot[] | null;
    stations?: Station[] | null;
    courses?: Course[] | null;
    use_factory?: any | null;
  };
}
