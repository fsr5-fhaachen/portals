declare namespace App.Models {
  type Duel = {
    id: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    competition_id: number;
    team_a_id: number;
    team_b_id: number;
    result: "team_a" | "draw" | "team_b";
    bonus_a: number;
    bonus_b: number;
    note: string | null;
    created_by: number | null;
    updated_by: number | null;
    competition?: Competition | null;
    team_a?: CompetitionTeam | null;
    team_b?: CompetitionTeam | null;
    creator?: Pick<User, "id" | "firstname" | "lastname"> | null;
    updater?: Pick<User, "id" | "firstname" | "lastname"> | null;
  };
}
