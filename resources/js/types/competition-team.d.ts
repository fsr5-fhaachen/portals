declare namespace App.Models {
  type CompetitionTeam = {
    id: number;
    created_at?: string /* Date */ | null;
    updated_at?: string /* Date */ | null;
    competition_id: number;
    group_id?: number | null;
    name: string;
    competition?: Competition | null;
    group?: Group | null;
  };
}
