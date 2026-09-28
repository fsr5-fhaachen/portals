<?php

namespace App\Http\Controllers;

use App\Helpers\DuelRecorder;
use App\Models\Competition;
use App\Models\Duel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class DashboardTutorScoringController extends Controller
{
    /**
     * Display the duel entry page for tutors
     */
    public function index(IlluminateRequest $request): Response
    {
        $competitions = Competition::where('is_open', true)
            ->orderBy('name')
            ->get(['id', 'name', 'points_win', 'points_draw', 'points_loss', 'bonus_pool', 'bonus_per_team', 'is_open']);

        $competition = $competitions->firstWhere('id', (int) $request->query('competition'));
        if (! $competition && $competitions->count() === 1) {
            $competition = $competitions->first();
        }

        return Inertia::render('Dashboard/Tutor/Scoring/Index', [
            'competitions' => $competitions,
            'competition' => $competition,
            'teams' => $competition ? $competition->teamsInNaturalOrder(['id', 'competition_id', 'name']) : [],
            'duels' => $competition ? DuelRecorder::listFor($competition) : [],
        ]);
    }

    /**
     * Record a new duel
     */
    public function storeDuel(IlluminateRequest $request): RedirectResponse
    {
        $competition = Competition::findOrFail($request->route('competition'));
        if (! $competition->is_open) {
            return self::closedResponse($competition);
        }

        $data = DuelRecorder::validate($competition);
        $duel = DuelRecorder::save(new Duel, $competition, $data, $request->user());

        Session::flash('success', 'Das Duell '.DuelRecorder::describe($duel).' wurde eingetragen.');

        return Redirect::route('dashboard.tutor.scoring.index', ['competition' => $competition->id]);
    }

    /**
     * Correct an existing duel
     */
    public function updateDuel(IlluminateRequest $request): RedirectResponse
    {
        $duel = Duel::findOrFail($request->route('duel'));
        $competition = $duel->competition;
        if (! $competition->is_open) {
            return self::closedResponse($competition);
        }

        $data = DuelRecorder::validate($competition);
        DuelRecorder::save($duel, $competition, $data, $request->user());

        Session::flash('success', 'Das Duell '.DuelRecorder::describe($duel).' wurde gespeichert.');

        return Redirect::route('dashboard.tutor.scoring.index', ['competition' => $competition->id]);
    }

    /**
     * Delete a duel
     */
    public function deleteDuel(IlluminateRequest $request): RedirectResponse
    {
        $duel = Duel::findOrFail($request->route('duel'));
        $competition = $duel->competition;
        if (! $competition->is_open) {
            return self::closedResponse($competition);
        }

        $description = DuelRecorder::describe($duel);
        $duel->delete();

        Session::flash('success', 'Das Duell '.$description.' wurde gelöscht.');

        return Redirect::route('dashboard.tutor.scoring.index', ['competition' => $competition->id]);
    }

    /**
     * Get the response for write attempts on a closed competition.
     */
    private static function closedResponse(Competition $competition): RedirectResponse
    {
        Session::flash('error', 'Der Wettbewerb <strong>'.e($competition->name).'</strong> ist geschlossen. Duelle können nicht mehr geändert werden.');

        return Redirect::route('dashboard.tutor.scoring.index');
    }
}
