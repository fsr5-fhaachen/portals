declare namespace App.Models {
  type Group = {
    updated_at: string /* Date */ | null;
    event_id: number;
    course_id: number | null;
    created_at: string /* Date */ | null;
    id: number;
    name: string;
    telegram_group_link: string | null;
    group_tutors?: GroupTutor[] | null;
    registrations?: Registration[] | null;
    stops?: Stop[] | null;
    course?: Course[] | null;
    event?: Event | null;
    tutors?: User[] | null;
    stations?: Station[] | null;
  };
}
