declare namespace App.Models {
    type StationTutor = {
        id: number;
        created_at: string /* Date */ | null;
        updated_at: string /* Date */ | null;
        user_id: number;
        station_id: number;
        user?: User | null;
        station?: Station | null;
    };
}
