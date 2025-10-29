declare namespace App.Models {
  type State = {
    id: number;
    key: string;
    value: any | null; // NOT FOUND;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    use_factory?: any | null;
  };
}
