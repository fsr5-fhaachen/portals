<?php

namespace Tests\Feature\Scoring;

use App\Models\Competition;
use App\Models\CompetitionTeam;
use App\Models\Duel;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TutorScoringTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;

    private Competition $competition;

    private CompetitionTeam $teamA;

    private CompetitionTeam $teamB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBasics();
        $this->activateModule('scoring');
        $this->tutor = $this->createUserWithRoles('tutor');
        $this->competition = Competition::factory()->withTeams(3)->create();
        [$this->teamA, $this->teamB] = $this->competition->teams()->get()->all();
    }

    /**
     * Get valid duel form data.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function duelData(array $overrides = []): array
    {
        return [
            'team_a_id' => $this->teamA->id,
            'team_b_id' => $this->teamB->id,
            'result' => Duel::RESULT_TEAM_A,
            'bonus_a' => 0,
            'bonus_b' => 0,
            'note' => null,
            ...$overrides,
        ];
    }

    /**
     * Post the duel form as tutor.
     *
     * @param  array<string, mixed>  $data
     */
    private function storeDuel(array $data): TestResponse
    {
        return $this->actingAsTutor($this->tutor)
            ->post(route('dashboard.tutor.scoring.storeDuel', ['competition' => $this->competition->id]), $data);
    }

    public function test_index_auto_selects_the_only_open_competition(): void
    {
        Competition::factory()->closed()->create();

        $this->actingAsTutor($this->tutor)
            ->get(route('dashboard.tutor.scoring.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Tutor/Scoring/Index')
                ->has('competitions', 1)
                ->where('competition.id', $this->competition->id)
                ->has('teams', 3)
                ->missing('standings')
            );
    }

    public function test_index_lets_the_tutor_choose_between_open_competitions(): void
    {
        $other = Competition::factory()->withTeams(2)->create();

        $this->actingAsTutor($this->tutor)
            ->get(route('dashboard.tutor.scoring.index'))
            ->assertInertia(fn (Assert $page) => $page->has('competitions', 2)->where('competition', null));

        $this->actingAsTutor($this->tutor)
            ->get(route('dashboard.tutor.scoring.index', ['competition' => $other->id]))
            ->assertInertia(fn (Assert $page) => $page->where('competition.id', $other->id)->has('teams', 2));
    }

    public function test_index_does_not_expose_sensitive_user_fields(): void
    {
        Duel::factory()->between($this->teamA, $this->teamB)->create(['created_by' => $this->tutor->id]);

        $response = $this->actingAsTutor($this->tutor)->get(route('dashboard.tutor.scoring.index'));

        $this->assertSame(
            ['id' => $this->tutor->id, 'firstname' => $this->tutor->firstname, 'lastname' => $this->tutor->lastname],
            $response->inertiaProps('duels.0.creator')
        );
    }

    public function test_tutor_can_record_a_duel(): void
    {
        $this->storeDuel($this->duelData(['bonus_a' => 1, 'note' => ' Knapp! ']))
            ->assertRedirect(route('dashboard.tutor.scoring.index', ['competition' => $this->competition->id]))
            ->assertSessionHas('success');

        $duel = Duel::sole();
        $this->assertSame($this->teamA->id, $duel->team_a_id);
        $this->assertSame($this->teamB->id, $duel->team_b_id);
        $this->assertSame(Duel::RESULT_TEAM_A, $duel->result);
        $this->assertSame(1, $duel->bonus_a);
        $this->assertSame('Knapp!', $duel->note);
        $this->assertSame($this->tutor->id, $duel->created_by);
        $this->assertNull($duel->updated_by);
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function invalidDuelProvider(): array
    {
        return [
            'invalid result' => [['result' => 'team_c']],
            'negative bonus' => [['bonus_a' => -1]],
            'bonus above the limit per team' => [['bonus_a' => 3]],
            'missing team' => [['team_b_id' => null]],
        ];
    }

    #[DataProvider('invalidDuelProvider')]
    public function test_invalid_duels_are_rejected(array $overrides): void
    {
        $this->storeDuel($this->duelData($overrides))->assertSessionHasErrors();

        $this->assertSame(0, Duel::count());
    }

    public function test_team_cannot_duel_itself(): void
    {
        $this->storeDuel($this->duelData(['team_b_id' => $this->teamA->id]))
            ->assertSessionHasErrors(['duel' => 'Ein Team kann nicht gegen sich selbst antreten.']);

        $this->assertSame(0, Duel::count());
    }

    public function test_team_of_another_competition_is_rejected(): void
    {
        $foreignTeam = CompetitionTeam::factory()->create();

        $this->storeDuel($this->duelData(['team_b_id' => $foreignTeam->id]))
            ->assertSessionHasErrors(['duel' => 'Beide Teams müssen zum Wettbewerb gehören.']);

        $this->assertSame(0, Duel::count());
    }

    /**
     * @return array<string, array{0: bool, 1: int, 2: int, 3: bool}>
     */
    public static function bonusProvider(): array
    {
        return [
            'per team: both teams get the maximum' => [true, 2, 2, true],
            'per team: one team gets the maximum' => [true, 2, 0, true],
            'per team: none' => [true, 0, 0, true],
            'per team: above the maximum' => [true, 3, 0, false],
            'shared pool: split' => [false, 1, 1, true],
            'shared pool: all to team A' => [false, 2, 0, true],
            'shared pool: none' => [false, 0, 0, true],
            'shared pool: above the pool' => [false, 2, 1, false],
            'shared pool: both teams get the maximum' => [false, 2, 2, false],
        ];
    }

    #[DataProvider('bonusProvider')]
    public function test_bonus_is_limited_per_team_or_by_a_shared_pool(bool $bonusPerTeam, int $bonusA, int $bonusB, bool $isAllowed): void
    {
        $this->competition->update(['bonus_per_team' => $bonusPerTeam]);

        $response = $this->storeDuel($this->duelData(['bonus_a' => $bonusA, 'bonus_b' => $bonusB]));

        if ($isAllowed) {
            $response->assertSessionHasNoErrors();
            $this->assertSame([$bonusA, $bonusB], [Duel::sole()->bonus_a, Duel::sole()->bonus_b]);
        } else {
            $response->assertSessionHasErrors(['duel' => $bonusPerTeam
                ? 'Jedes Team darf höchstens 2 Bonuspunkte bekommen.'
                : 'Der Bonus darf zusammen höchstens 2 Punkte betragen.']);
            $this->assertSame(0, Duel::count());
        }
    }

    public function test_teams_are_sorted_naturally(): void
    {
        $competition = Competition::factory()->create(['name' => 'Aaa']);
        foreach (['Gruppe 10', 'Gruppe 2', 'gruppe 11', 'Gruppe 1'] as $name) {
            CompetitionTeam::factory()->for($competition)->create(['name' => $name]);
        }

        $response = $this->actingAsTutor($this->tutor)->get(route('dashboard.tutor.scoring.index', ['competition' => $competition->id]));

        $this->assertSame(['Gruppe 1', 'Gruppe 2', 'Gruppe 10', 'gruppe 11'], array_column($response->inertiaProps('teams'), 'name'));
    }

    public function test_tutor_can_correct_a_duel_of_another_tutor(): void
    {
        $otherTutor = $this->createUserWithRoles('tutor');
        $duel = Duel::factory()->between($this->teamA, $this->teamB)->teamAWins()->create(['created_by' => $otherTutor->id]);

        $this->actingAsTutor($this->tutor)
            ->post(route('dashboard.tutor.scoring.updateDuel', ['duel' => $duel->id]), $this->duelData(['result' => Duel::RESULT_DRAW]))
            ->assertSessionHas('success');

        $duel->refresh();
        $this->assertSame(Duel::RESULT_DRAW, $duel->result);
        $this->assertSame($otherTutor->id, $duel->created_by);
        $this->assertSame($this->tutor->id, $duel->updated_by);
    }

    public function test_saving_an_unchanged_duel_keeps_the_updater(): void
    {
        $duel = Duel::factory()->between($this->teamA, $this->teamB)->teamAWins()->create(['created_by' => $this->tutor->id]);

        $this->actingAsTutor($this->tutor)
            ->post(route('dashboard.tutor.scoring.updateDuel', ['duel' => $duel->id]), $this->duelData());

        $this->assertNull($duel->fresh()->updated_by);
    }

    public function test_tutor_can_delete_a_duel(): void
    {
        $duel = Duel::factory()->between($this->teamA, $this->teamB)->create();

        $this->actingAsTutor($this->tutor)
            ->delete(route('dashboard.tutor.scoring.deleteDuel', ['duel' => $duel->id]))
            ->assertSessionHas('success');

        $this->assertSame(0, Duel::count());
    }

    public function test_closed_competition_blocks_all_changes(): void
    {
        $duel = Duel::factory()->between($this->teamA, $this->teamB)->teamAWins()->create();
        $this->competition->update(['is_open' => false]);

        $this->storeDuel($this->duelData())->assertSessionHas('error');
        $this->actingAsTutor($this->tutor)
            ->post(route('dashboard.tutor.scoring.updateDuel', ['duel' => $duel->id]), $this->duelData(['result' => Duel::RESULT_DRAW]))
            ->assertSessionHas('error');
        $this->actingAsTutor($this->tutor)
            ->delete(route('dashboard.tutor.scoring.deleteDuel', ['duel' => $duel->id]))
            ->assertSessionHas('error');

        $this->assertSame(1, Duel::count());
        $this->assertSame(Duel::RESULT_TEAM_A, $duel->fresh()->result);
    }

    public function test_student_is_redirected(): void
    {
        $this->actingAsTutor($this->createUserWithRoles())
            ->get(route('dashboard.tutor.scoring.index'))
            ->assertRedirect(route('dashboard.index'));
    }

    public function test_tutor_without_tutor_session_is_redirected_to_login(): void
    {
        $this->actingAs($this->tutor)
            ->post(route('dashboard.tutor.scoring.storeDuel', ['competition' => $this->competition->id]), $this->duelData())
            ->assertRedirect(route('dashboard.tutor.login'));

        $this->assertSame(0, Duel::count());
    }

    public function test_inactive_module_redirects(): void
    {
        Module::where('key', 'scoring')->update(['active' => false]);

        $this->actingAsTutor($this->tutor)
            ->get(route('dashboard.tutor.scoring.index'))
            ->assertRedirect(route('app.index'));
    }

    public function test_missing_module_row_redirects(): void
    {
        Module::where('key', 'scoring')->delete();

        $this->actingAsTutor($this->tutor)
            ->get(route('dashboard.tutor.scoring.index'))
            ->assertRedirect(route('app.index'));
    }
}
