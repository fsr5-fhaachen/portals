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
use Illuminate\Support\Collection;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

class ScoringAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;

    private User $admin;

    private Competition $competition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBasics();
        $this->activateModule('scoring');
        $this->tutor = $this->createUserWithRoles('tutor');
        $this->admin = $this->createUserWithRoles('admin');
        $this->competition = Competition::factory()->withTeams(2)->create();
        $this->enableModelAuditing();
    }

    /**
     * Get the duel audits in the order they were written.
     *
     * @return Collection<int, Audit>
     */
    private function duelAudits(): Collection
    {
        return Audit::where('auditable_type', Duel::class)->orderBy('id')->get();
    }

    public function test_duel_changes_are_audited_with_the_tutor_as_actor(): void
    {
        [$teamA, $teamB] = $this->competition->teams()->get()->all();
        $data = ['team_a_id' => $teamA->id, 'team_b_id' => $teamB->id, 'result' => Duel::RESULT_TEAM_A, 'bonus_a' => 0, 'bonus_b' => 0];

        $this->actingAsTutor($this->tutor)->post(route('dashboard.tutor.scoring.storeDuel', ['competition' => $this->competition->id]), $data);
        $duel = Duel::sole();
        $this->actingAsTutor($this->tutor)->post(route('dashboard.tutor.scoring.updateDuel', ['duel' => $duel->id]), [...$data, 'result' => Duel::RESULT_DRAW]);
        $this->actingAsTutor($this->tutor)->delete(route('dashboard.tutor.scoring.deleteDuel', ['duel' => $duel->id]));

        $audits = $this->duelAudits();
        $this->assertSame(['created', 'updated', 'deleted'], $audits->pluck('event')->all());
        $this->assertSame([$this->tutor->id], $audits->pluck('user_id')->map(fn ($id) => (int) $id)->unique()->values()->all());
        $this->assertSame(['result' => Duel::RESULT_TEAM_A, 'updated_by' => null], array_intersect_key($audits[1]->old_values, ['result' => 1, 'updated_by' => 1]));
        $this->assertSame(['result' => Duel::RESULT_DRAW, 'updated_by' => $this->tutor->id], array_intersect_key($audits[1]->new_values, ['result' => 1, 'updated_by' => 1]));
    }

    public function test_team_import_writes_one_audit_per_team(): void
    {
        $event = Event::factory()->create();
        Group::factory()->count(3)->for($event)->create();
        $competition = Competition::factory()->forEvent($event)->create();

        $this->actingAsTutor($this->admin)->post(route('dashboard.admin.scoring.importTeams', ['competition' => $competition->id]));

        $audits = Audit::where('auditable_type', CompetitionTeam::class)->where('event', 'created')->get();
        $this->assertCount(3, $audits);
        $this->assertSame([$this->admin->id], $audits->pluck('user_id')->map(fn ($id) => (int) $id)->unique()->values()->all());
    }

    public function test_deleting_a_competition_audits_every_child(): void
    {
        $this->competition->update(['is_open' => false]);
        [$teamA, $teamB] = $this->competition->teams()->get()->all();
        $duel = Duel::factory()->between($teamA, $teamB)->create();
        $extraPoint = ExtraPoint::factory()->for($this->competition)->create(['competition_team_id' => $teamA->id]);

        $this->actingAsTutor($this->admin)->delete(route('dashboard.admin.scoring.deleteCompetition', ['competition' => $this->competition->id]));

        $deleted = Audit::where('event', 'deleted')->get()->map(fn (Audit $audit) => $audit->auditable_type.'#'.$audit->auditable_id)->all();
        $this->assertEqualsCanonicalizing([
            Duel::class.'#'.$duel->id,
            ExtraPoint::class.'#'.$extraPoint->id,
            CompetitionTeam::class.'#'.$teamA->id,
            CompetitionTeam::class.'#'.$teamB->id,
            Competition::class.'#'.$this->competition->id,
        ], $deleted);
    }

    public function test_scoring_audits_are_readable_in_the_audit_log(): void
    {
        [$teamA, $teamB] = $this->competition->teams()->get()->all();
        $this->actingAsTutor($this->tutor)->post(route('dashboard.tutor.scoring.storeDuel', ['competition' => $this->competition->id]), [
            'team_a_id' => $teamA->id,
            'team_b_id' => $teamB->id,
            'result' => Duel::RESULT_TEAM_B,
            'bonus_a' => 0,
            'bonus_b' => 1,
        ]);

        $entry = $this->actingAsTutor($this->admin)
            ->get(route('dashboard.admin.auditLog.index', ['subject_type' => 'Duel']))
            ->inertiaProps('audits.data.0');

        $this->assertSame('Duell', $entry['subject_type_label']);
        $this->assertSame($teamA->name.' vs. '.$teamB->name, $entry['subject_label']);
        $this->assertSame($this->tutor->firstname.' '.$this->tutor->lastname, $entry['actor']['name']);
        $changes = collect($entry['changes'])->keyBy('field');
        $this->assertSame('Sieg Team B', $changes['result']['new']);
        $this->assertSame($this->competition->name, $changes['competition_id']['new']);
        $this->assertSame($teamA->name, $changes['team_a_id']['new']);
        $this->assertSame($this->tutor->firstname.' '.$this->tutor->lastname, $changes['created_by']['new']);
    }
}
