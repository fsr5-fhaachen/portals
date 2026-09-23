<?php

namespace App\Helpers;

use App\Models\Event;
use App\Models\Stop;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StationScheduleGenerator
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        protected Event $event,
        protected int $rounds,
    ) {}

    /**
     * Generate the station schedule for the event, replacing any existing one.
     */
    public function assign(): void
    {
        $pairing = $this->event->rally_config['pairing'] ?? 'duel';

        DB::transaction(function () use ($pairing): void {
            Stop::whereIn('station_id', $this->event->stations()->pluck('id'))->delete();

            if ($pairing === 'free') {
                $this->assignFree();
            } else {
                $this->assignDuel();
            }
        });
    }

    /**
     * Assign groups to stations in pairs (duels), using the circle method so that no two
     * groups ever meet twice. Requires exactly twice as many groups as stations.
     */
    protected function assignDuel(): void
    {
        $groups = $this->event->groups()->pluck('id')->all();
        $stations = $this->event->stations()->pluck('id')->all();

        $groupCount = count($groups);
        $stationCount = count($stations);

        if ($groupCount === 0 || $stationCount === 0 || $groupCount !== $stationCount * 2) {
            throw new RuntimeException(
                'Die Anzahl der Gruppen muss genau doppelt so groß sein wie die Anzahl der Stationen '.
                "(aktuell {$groupCount} Gruppen, {$stationCount} Stationen)."
            );
        }

        $rounds = min($this->rounds, $groupCount - 1);
        $rotating = $groups;

        // Tracks which stations each group has already visited, so the greedy assignment
        // below can spread groups across different stations instead of stacking them.
        $visitedStations = array_fill_keys($groups, []);

        for ($round = 1; $round <= $rounds; $round++) {
            $pairs = [];
            for ($i = 0; $i < $groupCount / 2; $i++) {
                $pairs[] = [$rotating[$i], $rotating[$groupCount - 1 - $i]];
            }

            $stationForPair = $this->matchPairsToStations($pairs, $stations, $visitedStations);

            foreach ($pairs as $pairIndex => $pair) {
                $stationId = $stationForPair[$pairIndex];

                $visitedStations[$pair[0]][] = $stationId;
                $visitedStations[$pair[1]][] = $stationId;

                Stop::create(['group_id' => $pair[0], 'station_id' => $stationId, 'round' => $round]);
                Stop::create(['group_id' => $pair[1], 'station_id' => $stationId, 'round' => $round]);
            }

            // Rotate all groups except the fixed first one.
            $last = array_pop($rotating);
            array_splice($rotating, 1, 0, [$last]);
        }
    }

    /**
     * Match this round's pairs to stations, preferring stations neither group in a pair has
     * visited yet. Uses Kuhn's algorithm to find a maximum matching on the "unvisited" edges
     * only, then fills any pairs it couldn't match that way with a leftover station (a repeat
     * visit is only used when it's genuinely unavoidable).
     *
     * This is a best-effort, round-by-round heuristic, not a globally optimal search: with the
     * fixed circle-method opponent rotation, a handful of repeat station visits per group can be
     * structurally unavoidable in later rounds. That's fine — the hard rule (no repeat opponents)
     * is guaranteed independently of this; a group revisiting a station is a cosmetic imperfection
     * for the map view, not a broken schedule.
     *
     * @param  array<array{0: int, 1: int}>  $pairs
     * @param  array<int>  $stations
     * @param  array<int, array<int>>  $visitedStations
     * @return array<int, int> pair index => station id
     */
    protected function matchPairsToStations(array $pairs, array $stations, array $visitedStations): array
    {
        $stationCount = count($stations);

        $adjacency = [];
        foreach ($pairs as $pairIndex => $pair) {
            $adjacency[$pairIndex] = [];
            foreach ($stations as $stationIndex => $stationId) {
                $alreadyVisited = in_array($stationId, $visitedStations[$pair[0]], true)
                    || in_array($stationId, $visitedStations[$pair[1]], true);

                if (! $alreadyVisited) {
                    $adjacency[$pairIndex][] = $stationIndex;
                }
            }
        }

        $stationToPair = array_fill(0, $stationCount, null);
        $pairToStation = array_fill(0, count($pairs), null);

        foreach (array_keys($pairs) as $pairIndex) {
            $visited = array_fill(0, $stationCount, false);
            $this->tryAugment($pairIndex, $adjacency, $visited, $stationToPair, $pairToStation);
        }

        $unmatchedStationIndexes = array_values(array_diff(range(0, $stationCount - 1), array_filter($pairToStation, fn ($index) => $index !== null)));
        foreach ($pairToStation as $pairIndex => $stationIndex) {
            if ($stationIndex === null) {
                $pairToStation[$pairIndex] = array_shift($unmatchedStationIndexes);
            }
        }

        $result = [];
        foreach ($pairToStation as $pairIndex => $stationIndex) {
            $result[$pairIndex] = $stations[$stationIndex];
        }

        return $result;
    }

    /**
     * Try to find an augmenting path for the given pair (standard Kuhn's algorithm step).
     *
     * @param  array<int, array<int>>  $adjacency
     * @param  array<int, bool>  $visited
     * @param  array<int, int|null>  $stationToPair
     * @param  array<int, int|null>  $pairToStation
     */
    protected function tryAugment(int $pairIndex, array $adjacency, array &$visited, array &$stationToPair, array &$pairToStation): bool
    {
        foreach ($adjacency[$pairIndex] as $stationIndex) {
            if ($visited[$stationIndex]) {
                continue;
            }
            $visited[$stationIndex] = true;

            if ($stationToPair[$stationIndex] === null || $this->tryAugment($stationToPair[$stationIndex], $adjacency, $visited, $stationToPair, $pairToStation)) {
                $stationToPair[$stationIndex] = $pairIndex;
                $pairToStation[$pairIndex] = $stationIndex;

                return true;
            }
        }

        return false;
    }

    /**
     * Assign groups to stations independently (no pairing), spreading them as evenly as
     * possible across stations and rounds.
     */
    protected function assignFree(): void
    {
        $groups = $this->event->groups()->pluck('id')->all();
        $stations = $this->event->stations()->pluck('id')->all();

        $stationCount = count($stations);

        if (count($groups) === 0 || $stationCount === 0) {
            throw new RuntimeException('Es müssen Gruppen und Stationen für dieses Event existieren, bevor ein Rundenplan generiert werden kann.');
        }

        foreach ($groups as $groupIndex => $groupId) {
            for ($round = 1; $round <= $this->rounds; $round++) {
                $stationIndex = ($groupIndex + $round - 1) % $stationCount;

                Stop::create([
                    'group_id' => $groupId,
                    'station_id' => $stations[$stationIndex],
                    'round' => $round,
                ]);
            }
        }
    }
}
