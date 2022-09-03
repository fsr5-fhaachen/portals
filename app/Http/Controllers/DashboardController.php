<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the dashboard index page
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
     * Display the dashboard test page 
     *
     * @return \Inertia\Response
     */
    public function test()
    {
        return Inertia::render('Dashboard/Test');
    }

    /**
     * Display the request cms page
     *
     * @return \Inertia\Response
     */
    public function cmsPage()
    {
        return Inertia::render('Dashboard/404');
    }
}
