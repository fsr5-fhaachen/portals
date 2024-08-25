declare namespace App.Models {
    type Registration = {
        id: number;
        created_at: string /* Date */ | null;
        updated_at: string /* Date */ | null;
        event_id: number;
        user_id: number;
        slot_id: number | null;
        group_id: number | null;
        drinks_alcohol: any | null // NOT FOUND;
        fulfils_requirements: any | null // NOT FOUND;
        is_present: any // NOT FOUND;
        form_responses: any[];
        queue_position: number | null;
        event?: Event | null;
        user?: User | null;
        slot?: Slot | null;
        group?: Group | null;
    };
}
