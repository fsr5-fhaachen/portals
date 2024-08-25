declare namespace App.Models {
    type GroupTutor = {
        id: number;
        created_at: string /* Date */ | null;
        updated_at: string /* Date */ | null;
        user_id: number;
        group_id: number;
        user?: User | null;
        group?: Group | null;
    };
}
