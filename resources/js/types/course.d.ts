declare namespace App.Models {
  type Course = {
    updated_at: string /* Date */ | null;
    created_at: string /* Date */ | null;
    show_on_registration: any; // NOT FOUND;
    id: number;
    classes: string;
    name: string;
    abbreviation: string;
    icon: string;
    users?: User[] | null;
    groups?: Group[] | null;
  };
}
