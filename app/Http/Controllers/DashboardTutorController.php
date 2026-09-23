<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Event;
use App\Models\Group;
use App\Models\Slot;
use App\Models\Station;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class DashboardTutorController extends Controller
{
    /**
     * Display the dashboard tutor index page
     */
    public function index(): Response
    {
        // get events ordered by sort_order
        $events = Event::orderBy('sort_order')->with('registrations')->with('courses')->get();

        return Inertia::render('Dashboard/Tutor/Index', [
            'events' => $events,
        ]);
    }

    /**
     * Display the dashboard tutor event page
     */
    public function event(Request $request): Response
    {
        $event = Event::find($request->event);
        if (! $event) {
            return Inertia::render('Dashboard/404');
        }
        $event->slots = $event->slots()->with('registrations')->get();
        $event->groups = $event->groups()->with(['registrations', 'courses'])->get();
        $event->registrations = $event->registrations()->with('user')->get();
        $event->stations = $event->stations()->get();

        $courses = Course::all();

        return Inertia::render('Dashboard/Tutor/Event', [
            'event' => $event,
            'courses' => $courses,
        ]);
    }

    /**
     * Display the dashboard tutor slot page
     */
    public function slot(Request $request): Response
    {
        $slot = Slot::with('event')->find($request->slot);
        if (! $slot) {
            return Inertia::render('Dashboard/404');
        }
        $slot->registrations = $slot->registrations()->with('user')->get();

        $courses = Course::all();

        return Inertia::render('Dashboard/Tutor/Slot', [
            'slot' => $slot,
            'courses' => $courses,
        ]);
    }

    /**
     * Display the dashboard tutor group page
     */
    public function group(Request $request): Response
    {
        $group = Group::with('event')->find($request->group);
        if (! $group) {
            return Inertia::render('Dashboard/404');
        }
        $group->registrations = $group->registrations()->with('user')->get();

        $courses = Course::all();

        return Inertia::render('Dashboard/Tutor/Group', [
            'group' => $group,
            'courses' => $courses,
            'isGroupTutor' => $group->tutors->contains(Auth::id()),
            'tasks' => $group->event->type == 'station_rally' ? $group->event->tasks : [],
        ]);
    }

    /**
     * Update the name of a group. Only allowed for tutors assigned to that group.
     */
    public function groupUpdateName(Request $request): RedirectResponse
    {
        $group = Group::find($request->group);
        if (! $group) {
            Session::flash('error', 'Die angegebene Gruppe existiert nicht');

            return Redirect::back();
        }

        if (! $group->tutors->contains(Auth::id())) {
            Session::flash('error', 'Du bist kein Gruppentutor dieser Gruppe');

            return Redirect::back();
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $group->update($validated);

        Session::flash('success', 'Der Gruppenname wurde erfolgreich geändert');

        return Redirect::back();
    }

    /**
     * Toggle a task's completion state for a group. Only allowed for tutors assigned to that group.
     */
    public function taskToggle(Request $request): RedirectResponse
    {
        $group = Group::find($request->group);
        if (! $group) {
            Session::flash('error', 'Die angegebene Gruppe existiert nicht');

            return Redirect::back();
        }

        $task = Task::find($request->task);
        if (! $task || $task->event_id != $group->event_id) {
            Session::flash('error', 'Die angegebene Aufgabe existiert nicht');

            return Redirect::back();
        }

        if (! $group->tutors->contains(Auth::id())) {
            Session::flash('error', 'Du bist kein Gruppentutor dieser Gruppe');

            return Redirect::back();
        }

        $completion = $task->completions()->firstOrNew(['group_id' => $group->id]);
        if ($completion->completed_at) {
            $completion->completed_at = null;
            $completion->completed_by = null;
        } else {
            $completion->completed_at = now();
            $completion->completed_by = Auth::id();
        }
        $completion->save();

        return Redirect::back();
    }

    /**
     * Display the dashboard tutor station page
     */
    public function station(Request $request): Response
    {
        $station = Station::with('event')->find($request->station);
        if (! $station) {
            return Inertia::render('Dashboard/404');
        }

        // the current duel is the earliest round at this station that hasn't been scored yet
        $currentRound = $station->stops()->whereNull('points')->min('round');

        $currentStops = $currentRound
            ? $station->stops()->where('round', $currentRound)->orderBy('group_id')->with('group.registrations.user')->get()
            : collect();

        return Inertia::render('Dashboard/Tutor/Station', [
            'station' => $station,
            'currentRound' => $currentRound,
            'currentStops' => $currentStops,
            'isStationTutor' => $station->tutors->contains(Auth::id()),
        ]);
    }

    /**
     * Submit the result for the current duel at a station
     */
    public function stationSubmitResult(Request $request): RedirectResponse
    {
        $station = Station::find($request->station);
        if (! $station) {
            Session::flash('error', 'Die angegebene Station existiert nicht');

            return Redirect::back();
        }

        if (! $station->tutors->contains(Auth::id())) {
            Session::flash('error', 'Du bist kein Stationstutor dieser Station');

            return Redirect::back();
        }

        $validated = $request->validate([
            'round' => ['required', 'integer', 'min:1'],
            'winner' => ['required', 'in:a,draw,b'],
            'bonus_a' => ['nullable', 'integer', 'min:0', 'max:2'],
            'bonus_b' => ['nullable', 'integer', 'min:0', 'max:2'],
        ]);

        $stops = $station->stops()->where('round', $validated['round'])->orderBy('group_id')->get();
        if ($stops->count() != 2) {
            Session::flash('error', 'Für diese Runde wurde kein gültiges Duell gefunden');

            return Redirect::back();
        }

        [$stopA, $stopB] = $stops;

        [$pointsA, $pointsB] = match ($validated['winner']) {
            'a' => [4, 0],
            'b' => [0, 4],
            default => [2, 2],
        };

        $stopA->update(['points' => $pointsA, 'bonus' => $validated['bonus_a'] ?? 0]);
        $stopB->update(['points' => $pointsB, 'bonus' => $validated['bonus_b'] ?? 0]);

        Session::flash('success', 'Das Ergebnis wurde erfolgreich gespeichert');

        return Redirect::back();
    }
}
