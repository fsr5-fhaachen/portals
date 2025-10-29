declare namespace App.Models {
  type Page = {
    id: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    title: string;
    slug: string;
    content: string;
    sort_order: number;
    use_factory?: any | null;
  };
}
