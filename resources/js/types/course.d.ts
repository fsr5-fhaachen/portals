declare namespace App.Models {
  type Course = {
    id: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    name: string;
    abbreviation: string;
    icon: string;
    show_on_registration: boolean;
    classes: string;
    users?: User[] | null;
    groups?: Group[] | null;
    use_factory?: any | null;
  };
}
