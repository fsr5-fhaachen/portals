declare namespace App.Models {
  type WorkshopSession = {
    id: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    workshop_id: number;
    workshop_block_id: number;
    location: string | null;
    minimum_participants: number;
    maximum_participants: number | null;
    is_cancelled: boolean;
    workshop?: Workshop | null;
    block?: WorkshopBlock | null;
    assignments?: WorkshopAssignment[] | null;
    assignments_count?: number;
  };
}
