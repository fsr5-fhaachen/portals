<?php

namespace Tests\Feature\Scoring;

use App\Helpers\ScoringStandings;
use App\Models\Competition;
use App\Models\CompetitionTeam;
use App\Models\Duel;
use App\Models\ExtraPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoringStandingsTest extends TestCase
{
    use RefreshDatabase;

    private Competition $competition;

    /**
     * @var array<string, CompetitionTeam>
     */
    private array $teams = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->competition = Competition::factory()->create();
        foreach (['Alpha', 'Bravo', 'Charlie', 'Delta'] as $name) {
            $this->teams[$name] = CompetitionTeam::factory()->for($this->competition)->create(['name' => $name]);
        }
    }

    /**
     * Get the standings keyed by team name.
     *
     * @return array<string, array<string, mixed>>
     */
    private function standings(): array
    {
        return collect(ScoringStandings::calculate($this->competition->fresh()))->keyBy('team_name')->all();
    }

    public function test_duels_are_scored_with_win_draw_and_loss_points(): void
    {
        Duel::factory()->between($this->teams['Alpha'], $this->teams['Bravo'])->teamAWins()->create();
        Duel::factory()->between($this->teams['Alpha'], $this->teams['Charlie'])->draw()->create();
        Duel::factory()->between($this->teams['Delta'], $this->teams['Charlie'])->teamBWins()->create();

        $standings = $this->standings();

        $this->assertSame(
            ['duels' => 2, 'wins' => 1, 'draws' => 1, 'losses' => 0, 'duel_points' => 6],
            array_intersect_key($standings['Alpha'], array_flip(['duels', 'wins', 'draws', 'losses', 'duel_points']))
        );
        $this->assertSame(0, $standings['Bravo']['duel_points']);
        $this->assertSame(1, $standings['Bravo']['losses']);
        $this->assertSame(6, $standings['Charlie']['duel_points']);
        $this->assertSame(0, $standings['Delta']['duel_points']);
    }

    public function test_custom_point_values_are_used(): void
    {
        $this->competition->update(['points_win' => 3, 'points_draw' => 1, 'points_loss' => 1]);
        Duel::factory()->between($this->teams['Alpha'], $this->teams['Bravo'])->teamAWins()->create();
        Duel::factory()->between($this->teams['Alpha'], $this->teams['Charlie'])->draw()->create();

        $standings = $this->standings();

        $this->assertSame(4, $standings['Alpha']['total']);
        $this->assertSame(1, $standings['Bravo']['total']);
        $this->assertSame(1, $standings['Charlie']['total']);
    }

    public function test_bonus_and_extra_points_are_added_to_the_total(): void
    {
        Duel::factory()->between($this->teams['Alpha'], $this->teams['Bravo'])->teamAWins()->withBonus(1, 1)->create();
        ExtraPoint::factory()->for($this->competition)->create(['competition_team_id' => $this->teams['Alpha']->id, 'points' => 3]);
        ExtraPoint::factory()->for($this->competition)->create(['competition_team_id' => $this->teams['Alpha']->id, 'points' => -5]);
        ExtraPoint::factory()->for($this->competition)->create(['competition_team_id' => $this->teams['Bravo']->id, 'points' => 2]);

        $standings = $this->standings();

        $this->assertSame(['duel_points' => 4, 'bonus' => 1, 'extra_points' => -2, 'total' => 3], array_intersect_key($standings['Alpha'], array_flip(['duel_points', 'bonus', 'extra_points', 'total'])));
        $this->assertSame(['duel_points' => 0, 'bonus' => 1, 'extra_points' => 2, 'total' => 3], array_intersect_key($standings['Bravo'], array_flip(['duel_points', 'bonus', 'extra_points', 'total'])));
    }

    public function test_tied_teams_share_a_rank_and_are_ordered_by_name(): void
    {
        Duel::factory()->between($this->teams['Delta'], $this->teams['Alpha'])->teamAWins()->create();
        Duel::factory()->between($this->teams['Charlie'], $this->teams['Bravo'])->draw()->create();

        $standings = ScoringStandings::calculate($this->competition);

        $this->assertSame(['Delta', 'Bravo', 'Charlie', 'Alpha'], array_column($standings, 'team_name'));
        $this->assertSame([1, 2, 2, 4], array_column($standings, 'rank'));
    }

    public function test_teams_without_duels_are_listed_with_zero_points(): void
    {
        $standings = ScoringStandings::calculate($this->competition);

        $this->assertCount(4, $standings);
        $this->assertSame([0, 0, 0, 0], array_column($standings, 'total'));
        $this->assertSame([1, 1, 1, 1], array_column($standings, 'rank'));
        $this->assertSame(['Alpha', 'Bravo', 'Charlie', 'Delta'], array_column($standings, 'team_name'));
    }

    public function test_configuration_changes_apply_to_existing_duels(): void
    {
        Duel::factory()->between($this->teams['Alpha'], $this->teams['Bravo'])->teamAWins()->create();
        $this->assertSame(4, $this->standings()['Alpha']['total']);

        $this->competition->update(['points_win' => 10]);

        $this->assertSame(10, $this->standings()['Alpha']['total']);
    }

    public function test_tied_teams_are_sorted_naturally(): void
    {
        $this->teams['Alpha']->update(['name' => 'Gruppe 10']);
        $this->teams['Bravo']->update(['name' => 'Gruppe 2']);

        $names = array_column(ScoringStandings::calculate($this->competition), 'team_name');

        $this->assertSame(['Charlie', 'Delta', 'Gruppe 2', 'Gruppe 10'], $names);
    }

    public function test_entries_of_other_competitions_are_ignored(): void
    {
        Duel::factory()->teamAWins()->create();
        ExtraPoint::factory()->create();

        $this->assertSame([0, 0, 0, 0], array_column(ScoringStandings::calculate($this->competition), 'total'));
    }
}
