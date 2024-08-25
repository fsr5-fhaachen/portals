declare namespace App.Models {
    type Group = {
        id: number;
        created_at: string /* Date */ | null;
        updated_at: string /* Date */ | null;
        event_id: number;
        course_id: number | null;
        name: string;
        group_tutors?: GroupTutor[] | null;
        registrations?: Registration[] | null;
        stops?: Stop[] | null;
        course?: Course | null;
        event?: Event | null;
        tutors?: User[] | null;
        stations?: Station[] | null;
    };
}
