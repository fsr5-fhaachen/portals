<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardAdminController extends Controller
{
    /**
     * Display the dashboard admin event page
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

        return Inertia::render('Dashboard/Admin/Event', [
            'event' => $event,
            'courses' => $courses,
        ]);
    }

    /**
     * Display the dashboard admin event submit page
     *
     * @param Request $request
     *
     * @return \Inertia\Response
     */
    public function eventSubmit(Request $request)
    {
        $event = Event::find($request->event);
        if (!$event) {
            return Inertia::render('Dashboard/404');
        }

        return Inertia::render('Dashboard/Admin/Submit', [
            'event' => $event,
        ]);
    }
}
