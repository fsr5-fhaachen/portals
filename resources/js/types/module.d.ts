declare namespace App.Models {
  type Module = {
    id: number;
    key: string;
    active: boolean;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    expose_public: boolean;
    use_factory?: any | null;
  };
}
