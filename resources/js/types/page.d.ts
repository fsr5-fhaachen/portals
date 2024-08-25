declare namespace App.Models {
    type Page = {
        created_at: string /* Date */ | null;
        updated_at: string /* Date */ | null;
        sort_order: number;
        id: number;
        title: string;
        slug: string;
        content: string;
    };
}
