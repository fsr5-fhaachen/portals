<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the index page of 
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        // get events ordered by registration_from
        $events = Event::orderBy('registration_from')->get();

        return Inertia::render('Dashboard/Index', [
            'events' => $events
        ]);
    }

    /**
     * Display the test page of 
     *
     * @return \Inertia\Response
     */
    public function test()
    {
        return Inertia::render('Dashboard/Test');
    }
}
