declare namespace App.Models {
    type Station = {
        id: number;
        created_at: string /* Date */ | null;
        updated_at: string /* Date */ | null;
        event_id: number;
        name: string;
        stops?: Stop[] | null;
        event?: Event | null;
        tutors?: User[] | null;
        groups?: Group[] | null;
    };
}
