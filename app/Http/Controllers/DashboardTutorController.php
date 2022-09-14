<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Event;
use App\Models\Group;
use App\Models\Slot;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardTutorController extends Controller
{
    /**
     * Display the dashboard tutor index page
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        // get events ordered by registration_from
        $events = Event::orderBy('registration_from')->with('registrations')->get();

        return Inertia::render('Dashboard/Tutor/Index', [
            'events' => $events,
        ]);
    }

    /**
     * Display the dashboard tutor event page
     *
     * @param Request $request
     *
     * @return \Inertia\Response
     */
    public function event(Request $request)
    {
        $event = Event::find($request->event);
        if (!$event) {
            return Inertia::render('Dashboard/404');
        }
        $event->slots = $event->slots()->with('registrations')->get();
        $event->groups = $event->groups()->with('registrations')->get();
        $event->registrations = $event->registrations()->with('user')->get();

        $courses = Course::all();

        return Inertia::render('Dashboard/Tutor/Event', [
            'event' => $event,
            'courses' => $courses,
        ]);
    }

    /**
     * Display the dashboard tutor slot page
     *
     * @param Request $request
     *
     * @return \Inertia\Response
     */
    public function slot(Request $request)
    {
        $slot = Slot::with('event')->find($request->slot);
        if (!$slot) {
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
     *
     * @param Request $request
     *
     * @return \Inertia\Response
     */
    public function group(Request $request)
    {
        $group = Group::with('event')->find($request->group);
        if (!$group) {
            return Inertia::render('Dashboard/404');
        }
        $group->registrations = $group->registrations()->with('user')->get();

        $courses = Course::all();

        return Inertia::render('Dashboard/Tutor/Group', [
            'group' => $group,
            'courses' => $courses,
        ]);
    }

    /**
     * Display the dashboard tutor registrations page
     *
     * @param Request $request
     *
     * @return \Inertia\Response
     */
    public function registrations(Request $request)
    {
        $event = Event::with('groups')->with('slots')->find($request->event);
        if (!$event) {
            return Inertia::render('Dashboard/404');
        }
        $event->registrations = $event->registrations()->with('user')->get();

        $courses = Course::all();

        return Inertia::render('Dashboard/Tutor/Registrations', [
            'event' => $event,
            'courses' => $courses,
        ]);
    }
}