declare namespace App.Models {
    type User = {
        created_at: string /* Date */ | null;
        course_id: number;
        updated_at: string /* Date */ | null;
        is_disabled: any // NOT FOUND;
        id: number;
        avatar: string | null;
        firstname: string;
        lastname: string;
        email: string;
        remember_token: string | null;
        station_tutors?: StationTutor[] | null;
        group_tutors?: GroupTutor[] | null;
        registrations?: Registration[] | null;
        course?: Course | null;
    };
}
