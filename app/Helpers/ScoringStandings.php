<?php

namespace App\Helpers;

use App\Models\Competition;
use App\Models\Duel;

class ScoringStandings
{
    /**
     * Calculate the standings of the competition with the current point configuration.
     *
     * Teams with the same total share a rank (1, 2, 2, 4) and are ordered naturally by name (Gruppe 2 before Gruppe 10).
     *
     * @return list<array{rank: int, team_id: int, team_name: string, duels: int, wins: int, draws: int, losses: int, duel_points: int, bonus: int, extra_points: int, total: int}>
     */
    public static function calculate(Competition $competition): array
    {
        $rows = [];
        foreach ($competition->teams()->get(['id', 'name']) as $team) {
            $rows[$team->id] = [
                'rank' => 0,
                'team_id' => $team->id,
                'team_name' => $team->name,
                'duels' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'duel_points' => 0,
                'bonus' => 0,
                'extra_points' => 0,
                'total' => 0,
            ];
        }

        $duels = $competition->duels()->get(['id', 'team_a_id', 'team_b_id', 'result', 'bonus_a', 'bonus_b']);
        foreach ($duels as $duel) {
            self::applyDuel($rows, $competition, $duel->team_a_id, $duel->outcomeForTeamA(), $duel->bonus_a);
            self::applyDuel($rows, $competition, $duel->team_b_id, $duel->outcomeForTeamB(), $duel->bonus_b);
        }

        $extraPoints = $competition->extraPoints()
            ->selectRaw('competition_team_id, SUM(points) as total')
            ->groupBy('competition_team_id')
            ->pluck('total', 'competition_team_id');
        foreach ($extraPoints as $teamId => $points) {
            if (isset($rows[$teamId])) {
                $rows[$teamId]['extra_points'] = (int) $points;
            }
        }

        foreach ($rows as &$row) {
            $row['total'] = $row['duel_points'] + $row['bonus'] + $row['extra_points'];
        }
        unset($row);

        usort($rows, fn (array $a, array $b) => $b['total'] <=> $a['total'] ?: strnatcasecmp($a['team_name'], $b['team_name']));

        $previousTotal = null;
        foreach ($rows as $index => &$row) {
            $row['rank'] = $row['total'] === $previousTotal ? $rows[$index - 1]['rank'] : $index + 1;
            $previousTotal = $row['total'];
        }
        unset($row);

        return $rows;
    }

    /**
     * Add the outcome of a duel to the row of a team.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private static function applyDuel(array &$rows, Competition $competition, int $teamId, string $outcome, int $bonus): void
    {
        if (! isset($rows[$teamId])) {
            return;
        }

        $rows[$teamId]['duels']++;
        $rows[$teamId][match ($outcome) {
            Duel::OUTCOME_WIN => 'wins',
            Duel::OUTCOME_DRAW => 'draws',
            default => 'losses',
        }]++;
        $rows[$teamId]['duel_points'] += $competition->pointsFor($outcome);
        $rows[$teamId]['bonus'] += $bonus;
    }
}
