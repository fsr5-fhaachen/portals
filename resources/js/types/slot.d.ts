declare namespace App.Models {
    type Slot = {
        form: any | null // NOT FOUND;
        id: number;
        created_at: string /* Date */ | null;
        updated_at: string /* Date */ | null;
        event_id: number;
        has_requirements: any // NOT FOUND;
        maximum_participants: number | null;
        name: string;
        registrations?: Registration[] | null;
        event?: Event | null;
    };
}
