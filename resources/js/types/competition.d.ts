declare namespace App.Models {
  type Competition = {
    id: number;
    created_at: string /* Date */ | null;
    updated_at: string /* Date */ | null;
    name: string;
    event_id: number | null;
    points_win: number;
    points_draw: number;
    points_loss: number;
    bonus_pool: number;
    bonus_per_team: boolean;
    is_open: boolean;
    event?: Event | null;
    teams?: CompetitionTeam[] | null;
    duels?: Duel[] | null;
    extra_points?: ExtraPoint[] | null;
    teams_count?: number;
    duels_count?: number;
  };
}
