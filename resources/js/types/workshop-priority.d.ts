declare namespace App.Models {
  type WorkshopPriority = {
    id: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    registration_id: number;
    workshop_id: number;
    priority: number;
    registration?: Registration | null;
    workshop?: Workshop | null;
  };
}
