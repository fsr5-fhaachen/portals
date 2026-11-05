declare namespace App.Models {
  type WorkshopAssignment = {
    id: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    registration_id: number;
    workshop_session_id: number;
    workshop_block_id: number;
    registration?: Registration | null;
    session?: WorkshopSession | null;
    block?: WorkshopBlock | null;
  };
}
