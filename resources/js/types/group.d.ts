declare namespace App.Models {
  type Group = {
    id: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    event_id: number;
    name: string;
    telegram_group_link: string | null;
    group_tutors?: GroupTutor[] | null;
    registrations?: Registration[] | null;
    stops?: Stop[] | null;
    courses?: Course[] | null;
    event?: Event | null;
    tutors?: User[] | null;
    stations?: Station[] | null;
  };
}
