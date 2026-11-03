<?php

namespace Tests\Feature\Scoring;

use App\Models\Competition;
use App\Models\CompetitionTeam;
use App\Models\Duel;
use App\Models\Event;
use App\Models\ExtraPoint;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminScoringTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBasics();
        $this->activateModule('scoring');
        $this->admin = $this->createUserWithRoles('admin');
    }

    /**
     * Get valid competition form data.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function competitionData(array $overrides = []): array
    {
        return [
            'name' => 'Stadtrallye',
            'event_id' => null,
            'points_win' => 4,
            'points_draw' => 2,
            'points_loss' => 0,
            'bonus_pool' => 2,
            'bonus_per_team' => true,
            ...$overrides,
        ];
    }

    public function test_admin_can_list_competitions(): void
    {
        Competition::factory()->withTeams(2)->create();

        $this->actingAsTutor($this->admin)
            ->get(route('dashboard.admin.scoring.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Admin/Scoring/Index')
                ->has('competitions', 1)
                ->where('competitions.0.teams_count', 2)
                ->has('events')
            );
    }

    public function test_admin_can_create_a_competition(): void
    {
        $event = Event::factory()->create();

        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.scoring.storeCompetition'), $this->competitionData(['event_id' => $event->id, 'points_win' => 3, 'points_draw' => 1]))
            ->assertRedirect(route('dashboard.admin.scoring.competition', ['competition' => Competition::sole()->id]));

        $competition = Competition::sole();
        $this->assertSame('Stadtrallye', $competition->name);
        $this->assertSame($event->id, $competition->event_id);
        $this->assertSame([3, 1, 0, 2], [$competition->points_win, $competition->points_draw, $competition->points_loss, $competition->bonus_pool]);
        $this->assertFalse($competition->is_open);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidCompetitionProvider(): array
    {
        return [
            'missing name' => [['name' => ''], 'name'],
            'draw worth more than win' => [['points_win' => 2, 'points_draw' => 3], 'points_draw'],
            'loss worth more than draw' => [['points_draw' => 1, 'points_loss' => 2], 'points_loss'],
            'negative bonus pool' => [['bonus_pool' => -1], 'bonus_pool'],
            'unknown event' => [['event_id' => 999], 'event_id'],
            'missing bonus mode' => [['bonus_per_team' => null], 'bonus_per_team'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidCompetitionProvider')]
    public function test_invalid_competitions_are_rejected(array $overrides, string $field): void
    {
        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.scoring.storeCompetition'), $this->competitionData($overrides))
            ->assertSessionHasErrors($field);

        $this->assertSame(0, Competition::count());
    }

    public function test_admin_can_view_a_competition(): void
    {
        $competition = Competition::factory()->withTeams(2)->create();

        $this->actingAsTutor($this->admin)
            ->get(route('dashboard.admin.scoring.competition', ['competition' => $competition->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Admin/Scoring/Competition')
                ->where('competition.id', $competition->id)
                ->has('teams', 2)
                ->has('standings', 2)
                ->has('duels', 0)
                ->has('extraPoints', 0)
                ->where('importableGroupsCount', 0)
            );
    }

    public function test_admin_can_update_and_toggle_a_competition(): void
    {
        $competition = Competition::factory()->closed()->create();

        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.scoring.updateCompetition', ['competition' => $competition->id]), $this->competitionData(['name' => 'Bingo', 'bonus_pool' => 0, 'bonus_per_team' => false]))
            ->assertSessionHas('success');
        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.scoring.toggleCompetitionOpen', ['competition' => $competition->id]))
            ->assertSessionHas('success');

        $competition->refresh();
        $this->assertSame('Bingo', $competition->name);
        $this->assertSame(0, $competition->bonus_pool);
        $this->assertFalse($competition->bonus_per_team);
        $this->assertTrue($competition->is_open);
    }

    public function test_open_competition_cannot_be_deleted(): void
    {
        $competition = Competition::factory()->create();

        $this->actingAsTutor($this->admin)
            ->delete(route('dashboard.admin.scoring.deleteCompetition', ['competition' => $competition->id]))
            ->assertSessionHas('error');

        $this->assertSame(1, Competition::count());
    }

    public function test_closed_competition_is_deleted_with_all_entries(): void
    {
        $competition = Competition::factory()->closed()->withTeams(2)->create();
        [$teamA, $teamB] = $competition->teams()->get()->all();
        Duel::factory()->between($teamA, $teamB)->create();
        ExtraPoint::factory()->for($competition)->create(['competition_team_id' => $teamA->id]);

        $this->actingAsTutor($this->admin)
            ->delete(route('dashboard.admin.scoring.deleteCompetition', ['competition' => $competition->id]))
            ->assertRedirect(route('dashboard.admin.scoring.index'));

        $this->assertSame(0, Competition::count());
        $this->assertSame(0, CompetitionTeam::count());
        $this->assertSame(0, Duel::count());
        $this->assertSame(0, ExtraPoint::count());
    }

    public function test_team_names_are_unique_per_competition(): void
    {
        $competition = Competition::factory()->create();
        CompetitionTeam::factory()->for($competition)->create(['name' => 'Gruppe 1']);
        $otherCompetition = Competition::factory()->create();

        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.scoring.storeTeam', ['competition' => $competition->id]), ['name' => 'Gruppe 1'])
            ->assertSessionHasErrors('name');
        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.scoring.storeTeam', ['competition' => $otherCompetition->id]), ['name' => 'Gruppe 1'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $competition->teams()->count());
        $this->assertSame(1, $otherCompetition->teams()->count());
    }

    public function test_admin_can_rename_a_team(): void
    {
        $team = CompetitionTeam::factory()->create(['name' => 'Alt']);

        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.scoring.updateTeam', ['team' => $team->id]), ['name' => 'Neu'])
            ->assertSessionHas('success');

        $this->assertSame('Neu', $team->fresh()->name);
    }

    public function test_teams_are_sorted_naturally(): void
    {
        $competition = Competition::factory()->create();
        foreach (['Gruppe 10', 'Gruppe 2', 'Gruppe 1'] as $name) {
            CompetitionTeam::factory()->for($competition)->create(['name' => $name]);
        }

        $response = $this->actingAsTutor($this->admin)->get(route('dashboard.admin.scoring.competition', ['competition' => $competition->id]));

        $this->assertSame(['Gruppe 1', 'Gruppe 2', 'Gruppe 10'], array_column($response->inertiaProps('teams'), 'name'));
    }

    public function test_team_with_entries_cannot_be_deleted(): void
    {
        $competition = Competition::factory()->withTeams(3)->create();
        [$teamA, $teamB, $unusedTeam] = $competition->teams()->get()->all();
        Duel::factory()->between($teamA, $teamB)->create();

        $this->actingAsTutor($this->admin)
            ->delete(route('dashboard.admin.scoring.deleteTeam', ['team' => $teamB->id]))
            ->assertSessionHas('error');
        $this->actingAsTutor($this->admin)
            ->delete(route('dashboard.admin.scoring.deleteTeam', ['team' => $unusedTeam->id]))
            ->assertSessionHas('success');

        $this->assertEqualsCanonicalizing([$teamA->id, $teamB->id], $competition->teams()->pluck('id')->all());
    }

    public function test_import_is_idempotent_and_links_teams_with_the_same_name(): void
    {
        $event = Event::factory()->create();
        $group1 = Group::factory()->for($event)->create(['name' => 'Gruppe 1']);
        $group2 = Group::factory()->for($event)->create(['name' => 'Gruppe 2']);
        $competition = Competition::factory()->forEvent($event)->create();
        $manualTeam = CompetitionTeam::factory()->for($competition)->create(['name' => 'gruppe 1']);

        $this->actingAsTutor($this->admin)
            ->get(route('dashboard.admin.scoring.competition', ['competition' => $competition->id]))
            ->assertInertia(fn (Assert $page) => $page->where('importableGroupsCount', 2));

        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.scoring.importTeams', ['competition' => $competition->id]))
            ->assertSessionHas('success');
        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.scoring.importTeams', ['competition' => $competition->id]))
            ->assertSessionHas('success');

        $this->assertSame(2, $competition->teams()->count());
        $this->assertSame($group1->id, $manualTeam->fresh()->group_id);
        $this->assertTrue($competition->teams()->where('group_id', $group2->id)->where('name', 'Gruppe 2')->exists());

        $this->actingAsTutor($this->admin)
            ->get(route('dashboard.admin.scoring.competition', ['competition' => $competition->id]))
            ->assertInertia(fn (Assert $page) => $page->where('importableGroupsCount', 0));
    }

    public function test_import_requires_an_event(): void
    {
        $competition = Competition::factory()->create();

        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.scoring.importTeams', ['competition' => $competition->id]))
            ->assertSessionHas('error');
    }

    public function test_admin_can_grant_extra_points_and_penalties(): void
    {
        $competition = Competition::factory()->withTeams(1)->create();
        $team = $competition->teams()->sole();

        foreach ([3, -2] as $points) {
            $this->actingAsTutor($this->admin)
                ->post(route('dashboard.admin.scoring.storeExtraPoint', ['competition' => $competition->id]), [
                    'competition_team_id' => $team->id,
                    'points' => $points,
                    'reason' => 'Bingo',
                ])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame([3, -2], ExtraPoint::orderBy('id')->pluck('points')->all());
        $this->assertSame($this->admin->id, ExtraPoint::first()->created_by);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidExtraPointProvider(): array
    {
        return [
            'zero points' => [['points' => 0], 'points'],
            'too many points' => [['points' => 101], 'points'],
            'missing reason' => [['reason' => ''], 'reason'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidExtraPointProvider')]
    public function test_invalid_extra_points_are_rejected(array $overrides, string $field): void
    {
        $competition = Competition::factory()->withTeams(1)->create();

        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.scoring.storeExtraPoint', ['competition' => $competition->id]), [
                'competition_team_id' => $competition->teams()->sole()->id,
                'points' => 2,
                'reason' => 'Bingo',
                ...$overrides,
            ])
            ->assertSessionHasErrors($field);

        $this->assertSame(0, ExtraPoint::count());
    }

    public function test_extra_points_for_a_team_of_another_competition_are_rejected(): void
    {
        $competition = Competition::factory()->create();

        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.scoring.storeExtraPoint', ['competition' => $competition->id]), [
                'competition_team_id' => CompetitionTeam::factory()->create()->id,
                'points' => 2,
                'reason' => 'Bingo',
            ])
            ->assertSessionHasErrors('competition_team_id');
    }

    public function test_admin_can_update_and_delete_extra_points(): void
    {
        $extraPoint = ExtraPoint::factory()->create(['points' => 2]);

        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.scoring.updateExtraPoint', ['extraPoint' => $extraPoint->id]), [
                'competition_team_id' => $extraPoint->competition_team_id,
                'points' => -5,
                'reason' => 'Regelverstoß',
            ])
            ->assertSessionHas('success');

        $extraPoint->refresh();
        $this->assertSame(-5, $extraPoint->points);
        $this->assertSame($this->admin->id, $extraPoint->updated_by);

        $this->actingAsTutor($this->admin)
            ->delete(route('dashboard.admin.scoring.deleteExtraPoint', ['extraPoint' => $extraPoint->id]))
            ->assertSessionHas('success');
        $this->assertSame(0, ExtraPoint::count());
    }

    public function test_admin_can_change_duels_of_a_closed_competition(): void
    {
        $competition = Competition::factory()->closed()->withTeams(2)->create();
        [$teamA, $teamB] = $competition->teams()->get()->all();
        $duel = Duel::factory()->between($teamA, $teamB)->teamAWins()->create();
        $data = ['team_a_id' => $teamA->id, 'team_b_id' => $teamB->id, 'result' => Duel::RESULT_TEAM_B, 'bonus_a' => 0, 'bonus_b' => 2];

        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.scoring.storeDuel', ['competition' => $competition->id]), $data)
            ->assertSessionHas('success');
        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.scoring.updateDuel', ['duel' => $duel->id]), $data)
            ->assertSessionHas('success');
        $this->assertSame(Duel::RESULT_TEAM_B, $duel->fresh()->result);
        $this->assertSame($this->admin->id, $duel->fresh()->updated_by);

        $this->actingAsTutor($this->admin)
            ->delete(route('dashboard.admin.scoring.deleteDuel', ['duel' => $duel->id]))
            ->assertSessionHas('success');
        $this->assertSame(1, Duel::count());
    }

    public function test_admin_duels_are_validated_too(): void
    {
        $competition = Competition::factory()->closed()->sharedBonusPool()->withTeams(2)->create();
        [$teamA, $teamB] = $competition->teams()->get()->all();

        $this->actingAsTutor($this->admin)
            ->post(route('dashboard.admin.scoring.storeDuel', ['competition' => $competition->id]), [
                'team_a_id' => $teamA->id,
                'team_b_id' => $teamB->id,
                'result' => Duel::RESULT_DRAW,
                'bonus_a' => 2,
                'bonus_b' => 2,
            ])
            ->assertSessionHasErrors('duel');
    }

    public function test_stage_tutor_is_redirected(): void
    {
        $this->actingAsTutor($this->createUserWithRoles('stage tutor'))
            ->get(route('dashboard.admin.scoring.index'))
            ->assertRedirect(route('dashboard.index'));
    }

    public function test_admin_without_permission_is_forbidden(): void
    {
        Role::findByName('admin')->revokePermissionTo('manage scoring');

        $this->actingAsTutor($this->admin)
            ->get(route('dashboard.admin.scoring.index'))
            ->assertForbidden();
    }
}
