<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardEventController extends Controller
{
    /**
     * Display the event index page 
     *
     * @param Request $request
     * 
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        // get event by id
        $event = Event::find($request->event);

        return Inertia::render('Dashboard/Event/Index', [
            'event' => $event
        ]);
    }

    /**
     * Display the event register page
     *
     * @param Request $request
     * 
     * @return \Inertia\Response
     */
    public function register(Request $request)
    {
        // get event by id with slots
        $event = Event::find($request->event);
        $event->slots = $event->slots()->get();

        return Inertia::render('Dashboard/Event/Register', [
            'event' => $event
        ]);
    }

    /**
     * Display the event unregister page
     *
     * @param Request $request
     * 
     * @return \Inertia\Response
     */
    public function unregister(Request $request)
    {
        // get event by id
        $event = Event::find($request->event);

        return Inertia::render('Dashboard/Event/Unregister', [
            'event' => $event
        ]);
    }
}
