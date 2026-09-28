declare namespace App.Models {
  type ExtraPoint = {
    id: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    competition_id: number;
    competition_team_id: number;
    points: number;
    reason: string;
    created_by: number | null;
    updated_by: number | null;
    competition?: Competition | null;
    team?: CompetitionTeam | null;
    creator?: Pick<User, "id" | "firstname" | "lastname"> | null;
    updater?: Pick<User, "id" | "firstname" | "lastname"> | null;
  };
}
