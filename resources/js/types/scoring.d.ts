declare namespace Scoring {
  type StandingRow = {
    rank: number;
    team_id: number;
    team_name: string;
    duels: number;
    wins: number;
    draws: number;
    losses: number;
    duel_points: number;
    bonus: number;
    extra_points: number;
    total: number;
  };

  type TeamRow = {
    id: number;
    competition_id: number;
    group_id: number | null;
    name: string;
    is_used: boolean;
  };
}
