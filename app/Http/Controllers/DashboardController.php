<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // get registrations of the user
        $registrations = Auth::user()->registrations;

        return Inertia::render('Dashboard/Index', [
            'events' => $events,
            'registrations' => $registrations
        ]);
    }

    /**
     * Display the request cms page
     *
     * @param Request $request
     * 
     * @return \Inertia\Response
     */
    public function cmsPage(Request $request)
    {
        $page = Page::where('slug', $request->slug)->first();

        if(!$page) {
            return Inertia::render('Dashboard/404');
        }

        return Inertia::render('Dashboard/Page', [
            'page' => $page
        ]);
    }
}
