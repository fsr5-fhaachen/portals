<?php

namespace App\Http\Controllers;

use App\Helpers\DuelRecorder;
use App\Helpers\ScoringStandings;
use App\Models\Competition;
use App\Models\CompetitionTeam;
use App\Models\Duel;
use App\Models\Event;
use App\Models\ExtraPoint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DashboardAdminScoringController extends Controller
{
    /**
     * Display all competitions
     */
    public function index(): Response
    {
        return Inertia::render('Dashboard/Admin/Scoring/Index', [
            'competitions' => Competition::with('event:id,name')
                ->withCount(['teams', 'duels'])
                ->orderBy('name')
                ->get(),
            'events' => Event::orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    /**
     * Create a competition
     */
    public function storeCompetition(): RedirectResponse
    {
        $validated = Request::validate(self::competitionRules(), self::competitionMessages());

        $competition = Competition::create($validated);

        Session::flash('success', 'Der Wettbewerb <strong>'.e($competition->name).'</strong> wurde angelegt. Öffne ihn, sobald die Teams stehen.');

        return Redirect::route('dashboard.admin.scoring.competition', ['competition' => $competition->id]);
    }

    /**
     * Display a competition with standings, teams, duels and extra points
     */
    public function showCompetition(IlluminateRequest $request): Response
    {
        $competition = Competition::with('event:id,name')->findOrFail($request->route('competition'));

        $teams = $competition->teamsInNaturalOrder(['id', 'competition_id', 'group_id', 'name'], ['duelsAsTeamA', 'duelsAsTeamB', 'extraPoints']);

        $importableGroupsCount = 0;
        if ($competition->event_id) {
            $importableGroupsCount = $competition->event->groups()
                ->whereNotIn('id', $teams->pluck('group_id')->filter())
                ->count();
        }

        return Inertia::render('Dashboard/Admin/Scoring/Competition', [
            'competition' => $competition,
            'events' => Event::orderBy('sort_order')->get(['id', 'name']),
            'teams' => $teams->map(fn (CompetitionTeam $team) => [
                'id' => $team->id,
                'competition_id' => $team->competition_id,
                'group_id' => $team->group_id,
                'name' => $team->name,
                'is_used' => $team->duels_as_team_a_count + $team->duels_as_team_b_count + $team->extra_points_count > 0,
            ]),
            'duels' => DuelRecorder::listFor($competition),
            'extraPoints' => $competition->extraPoints()
                ->with([
                    'team:id,name',
                    'creator:id,firstname,lastname',
                    'updater:id,firstname,lastname',
                ])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(),
            'standings' => ScoringStandings::calculate($competition),
            'importableGroupsCount' => $importableGroupsCount,
        ]);
    }

    /**
     * Update the settings of a competition
     */
    public function updateCompetition(IlluminateRequest $request): RedirectResponse
    {
        $competition = Competition::findOrFail($request->route('competition'));

        $validated = Request::validate(self::competitionRules(), self::competitionMessages());

        $competition->fill($validated)->save();

        Session::flash('success', 'Die Einstellungen von <strong>'.e($competition->name).'</strong> wurden gespeichert.');

        return Redirect::back();
    }

    /**
     * Open or close a competition for tutors
     */
    public function toggleCompetitionOpen(IlluminateRequest $request): RedirectResponse
    {
        $competition = Competition::findOrFail($request->route('competition'));

        $competition->is_open = ! $competition->is_open;
        $competition->save();

        Session::flash('success', 'Der Wettbewerb <strong>'.e($competition->name).'</strong> ist jetzt '.($competition->is_open ? 'offen. Tutoren können Duelle eintragen.' : 'geschlossen.'));

        return Redirect::back();
    }

    /**
     * Delete a closed competition with all of its teams, duels and extra points
     */
    public function deleteCompetition(IlluminateRequest $request): RedirectResponse
    {
        $competition = Competition::findOrFail($request->route('competition'));

        if ($competition->is_open) {
            Session::flash('error', 'Nur geschlossene Wettbewerbe können gelöscht werden.');

            return Redirect::back();
        }

        // delete every child on its own, so each deletion is audited
        DB::transaction(function () use ($competition) {
            $competition->duels()->get()->each->delete();
            $competition->extraPoints()->get()->each->delete();
            $competition->teams()->get()->each->delete();
            $competition->delete();
        });

        Session::flash('success', 'Der Wettbewerb <strong>'.e($competition->name).'</strong> wurde gelöscht.');

        return Redirect::route('dashboard.admin.scoring.index');
    }

    /**
     * Add a team to a competition
     */
    public function storeTeam(IlluminateRequest $request): RedirectResponse
    {
        $competition = Competition::findOrFail($request->route('competition'));

        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('competition_teams', 'name')->where('competition_id', $competition->id)],
        ], ['name.unique' => 'Ein Team mit diesem Namen gibt es in diesem Wettbewerb schon.']);

        $team = $competition->teams()->create(['name' => trim($validated['name'])]);

        Session::flash('success', 'Das Team <strong>'.e($team->name).'</strong> wurde angelegt.');

        return Redirect::back();
    }

    /**
     * Rename a team
     */
    public function updateTeam(IlluminateRequest $request): RedirectResponse
    {
        $team = CompetitionTeam::findOrFail($request->route('team'));

        $validated = Request::validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('competition_teams', 'name')->where('competition_id', $team->competition_id)->ignore($team->id)],
        ], ['name.unique' => 'Ein Team mit diesem Namen gibt es in diesem Wettbewerb schon.']);

        $team->name = trim($validated['name']);
        $team->save();

        Session::flash('success', 'Das Team <strong>'.e($team->name).'</strong> wurde gespeichert.');

        return Redirect::back();
    }

    /**
     * Delete a team without duels and extra points
     */
    public function deleteTeam(IlluminateRequest $request): RedirectResponse
    {
        $team = CompetitionTeam::findOrFail($request->route('team'));

        if ($team->hasScoringEntries()) {
            Session::flash('error', 'Das Team <strong>'.e($team->name).'</strong> hat bereits Duelle oder Sonderpunkte und kann nicht gelöscht werden.');

            return Redirect::back();
        }

        $team->delete();

        Session::flash('success', 'Das Team <strong>'.e($team->name).'</strong> wurde gelöscht.');

        return Redirect::back();
    }

    /**
     * Import the groups of the linked event as teams
     */
    public function importTeams(IlluminateRequest $request): RedirectResponse
    {
        $competition = Competition::findOrFail($request->route('competition'));

        if (! $competition->event_id) {
            Session::flash('error', 'Dem Wettbewerb ist keine Veranstaltung zugeordnet.');

            return Redirect::back();
        }

        $teams = $competition->teams()->get();
        $created = 0;
        $linked = 0;
        $skipped = [];

        foreach ($competition->event->groups()->orderBy('name')->get(['id', 'name']) as $group) {
            if ($teams->contains('group_id', $group->id)) {
                continue;
            }

            $teamWithSameName = $teams->first(fn (CompetitionTeam $team) => mb_strtolower($team->name) === mb_strtolower($group->name));
            if ($teamWithSameName && $teamWithSameName->group_id === null) {
                $teamWithSameName->group_id = $group->id;
                $teamWithSameName->save();
                $linked++;

                continue;
            }
            if ($teamWithSameName) {
                $skipped[] = $group->name;

                continue;
            }

            $teams->push($competition->teams()->create([
                'group_id' => $group->id,
                'name' => $group->name,
            ]));
            $created++;
        }

        $message = $created.' Teams wurden angelegt und '.$linked.' bestehende Teams verknüpft.';
        if ($skipped) {
            $message .= ' Übersprungen, weil der Name schon vergeben ist: <strong>'.e(implode(', ', $skipped)).'</strong>';
        }
        Session::flash($skipped ? 'warning' : 'success', $message);

        return Redirect::back();
    }

    /**
     * Record a duel, also in closed competitions
     */
    public function storeDuel(IlluminateRequest $request): RedirectResponse
    {
        $competition = Competition::findOrFail($request->route('competition'));

        $data = DuelRecorder::validate($competition);
        $duel = DuelRecorder::save(new Duel, $competition, $data, $request->user());

        Session::flash('success', 'Das Duell '.DuelRecorder::describe($duel).' wurde eingetragen.');

        return Redirect::back();
    }

    /**
     * Correct a duel, also in closed competitions
     */
    public function updateDuel(IlluminateRequest $request): RedirectResponse
    {
        $duel = Duel::findOrFail($request->route('duel'));

        $data = DuelRecorder::validate($duel->competition);
        DuelRecorder::save($duel, $duel->competition, $data, $request->user());

        Session::flash('success', 'Das Duell '.DuelRecorder::describe($duel).' wurde gespeichert.');

        return Redirect::back();
    }

    /**
     * Delete a duel, also in closed competitions
     */
    public function deleteDuel(IlluminateRequest $request): RedirectResponse
    {
        $duel = Duel::findOrFail($request->route('duel'));

        $description = DuelRecorder::describe($duel);
        $duel->delete();

        Session::flash('success', 'Das Duell '.$description.' wurde gelöscht.');

        return Redirect::back();
    }

    /**
     * Grant extra points (or a penalty) to a team
     */
    public function storeExtraPoint(IlluminateRequest $request): RedirectResponse
    {
        $competition = Competition::findOrFail($request->route('competition'));

        $validated = Request::validate(self::extraPointRules($competition), self::extraPointMessages());

        $extraPoint = new ExtraPoint($validated);
        $extraPoint->competition_id = $competition->id;
        $extraPoint->created_by = $request->user()->id;
        $extraPoint->save();

        Session::flash('success', 'Die Sonderpunkte für <strong>'.e($extraPoint->team->name).'</strong> wurden eingetragen.');

        return Redirect::back();
    }

    /**
     * Update extra points
     */
    public function updateExtraPoint(IlluminateRequest $request): RedirectResponse
    {
        $extraPoint = ExtraPoint::findOrFail($request->route('extraPoint'));

        $validated = Request::validate(self::extraPointRules($extraPoint->competition), self::extraPointMessages());

        $extraPoint->fill($validated);
        if ($extraPoint->isDirty()) {
            $extraPoint->updated_by = $request->user()->id;
        }
        $extraPoint->save();

        Session::flash('success', 'Die Sonderpunkte für <strong>'.e($extraPoint->team->name).'</strong> wurden gespeichert.');

        return Redirect::back();
    }

    /**
     * Delete extra points
     */
    public function deleteExtraPoint(IlluminateRequest $request): RedirectResponse
    {
        $extraPoint = ExtraPoint::findOrFail($request->route('extraPoint'));

        $extraPoint->delete();

        Session::flash('success', 'Die Sonderpunkte für <strong>'.e($extraPoint->team?->name).'</strong> wurden gelöscht.');

        return Redirect::back();
    }

    /**
     * Get the validation rules for the settings of a competition.
     *
     * @return array<string, list<mixed>>
     */
    private static function competitionRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'points_win' => ['required', 'integer', 'min:0', 'max:100'],
            'points_draw' => ['required', 'integer', 'min:0', 'max:100', 'lte:points_win'],
            'points_loss' => ['required', 'integer', 'min:0', 'max:100', 'lte:points_draw'],
            'bonus_pool' => ['required', 'integer', 'min:0', 'max:100'],
            'bonus_per_team' => ['required', 'boolean'],
        ];
    }

    /**
     * Get the validation rules for extra points.
     *
     * @return array<string, list<mixed>>
     */
    private static function extraPointRules(Competition $competition): array
    {
        return [
            'competition_team_id' => ['required', 'integer', Rule::exists('competition_teams', 'id')->where('competition_id', $competition->id)],
            'points' => ['required', 'integer', 'min:-100', 'max:100', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Get the messages for rules that only the server checks.
     *
     * @return array<string, string>
     */
    private static function competitionMessages(): array
    {
        return [
            'points_draw.lte' => 'Ein Unentschieden darf nicht mehr Punkte geben als ein Sieg.',
            'points_loss.lte' => 'Eine Niederlage darf nicht mehr Punkte geben als ein Unentschieden.',
        ];
    }

    /**
     * Get the messages for rules that only the server checks.
     *
     * @return array<string, string>
     */
    private static function extraPointMessages(): array
    {
        return [
            'competition_team_id.exists' => 'Das Team gehört nicht zu diesem Wettbewerb.',
            'points.not_in' => 'Sonderpunkte dürfen nicht 0 sein.',
        ];
    }
}
