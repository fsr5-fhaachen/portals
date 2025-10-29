declare namespace App.Models {
  type Station = {
    id: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    name: string;
    event_id: number;
    stops?: Stop[] | null;
    event?: Event | null;
    tutors?: User[] | null;
    groups?: Group[] | null;
    use_factory?: any | null;
  };
}
