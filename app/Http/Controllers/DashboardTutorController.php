<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Event;
use App\Models\Group;
use App\Models\Slot;
use Illuminate\Http\Request;
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
        $event->groups = $event->groups()->with('registrations')->get();
        foreach ($event->groups as $group) {
            $group->courses = $group->courses()->get();
        }
        $event->registrations = $event->registrations()->with('user')->get();

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
        ]);
    }
}
