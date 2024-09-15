<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard index page
     */
    public function index(): Response
    {
        // get events ordered by sort_order
        $events = Event::orderBy('sort_order')->get();
        foreach ($events as $event) {
            $event->courses = $event->courses()->get();
        }

        // get registrations of the user
        $registrations = Auth::user()->registrations;

        return Inertia::render('Dashboard/Index', [
            'events' => $events,
            'registrations' => $registrations,
        ]);
    }

    /**
     * Login a tutor
     */
    public function loginTutor(Request $request): RedirectResponse
    {
        $neededPassword = '';
        $successMessage = '';

        // check we need a password
        if (Auth::user()->hasRole(['admin', 'super admin'])) {
            $neededPassword = config('app.admin_password');
            $successMessage = 'Du wurdest als Admin angemeldet.';
        } elseif (Auth::user()->hasRole(['esa', 'stage tutor', 'tutor'])) {
            $neededPassword = config('app.tutor_password');
            $successMessage = 'Du wurdest als Tutor angemeldet.';
        }

        if ($neededPassword) {
            if ($request->input('password') == $neededPassword) {
                // set the session variable
                session(['tutor' => true]);

                Session::flash('success', $successMessage);

                // redirect to dashboard
                return redirect()->route('dashboard.tutor.index');
            } else {
                Session::flash('error', 'Das Passwort ist falsch.');

                // redirect to dashboard
                return redirect()->route('dashboard.index');
            }
        }
    }

    /**
     * Display the request cms page
     */
    public function cmsPage(Request $request): Response
    {
        $page = Page::where('slug', $request->slug)->first();

        if (! $page) {
            return Inertia::render('Dashboard/404');
        }

        return Inertia::render('Dashboard/Page', [
            'page' => $page,
        ]);
    }
}
