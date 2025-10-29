declare namespace App.Models {
  type User = {
    id: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    firstname: string;
    lastname: string;
    email: string;
    course_id: number;
    remember_token: string | null;
    is_disabled: boolean;
    avatar: string | null;
    pin: number | null;
    station_tutors?: StationTutor[] | null;
    group_tutors?: GroupTutor[] | null;
    registrations?: Registration[] | null;
    course?: Course | null;
    use_factory?: any | null;
  };
}
