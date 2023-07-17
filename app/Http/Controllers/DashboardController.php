<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use App\Models\Event;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the dashboard index page
     *
     * @return \Inertia\Response
     */
    public function index(): Response
    {
        // get events ordered by sort_order
        $events = Event::orderBy('sort_order')->get();

        // get registrations of the user
        $registrations = Auth::user()->registrations;

        return Inertia::render('Dashboard/Index', [
            'events' => $events,
            'registrations' => $registrations,
        ]);
    }

    /**
     * Login a tutor
     *
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function loginTutor(Request $request): RedirectResponse
    {
        // check if the user is a tutor
        if (Auth::user()->is_tutor) {
            $neededPassword = config('app.tutor_password');

            // check if user is admin
            if (Auth::user()->is_admin) {
                $neededPassword = config('app.admin_password');
            }

            // check if password is tutor_password
            if ($request->input('password') == $neededPassword) {
                // set the session variable
                session(['tutor' => true]);

                Session::flash('success', 'Du wurdest als Tutor angemeldet.');

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
     *
     *
     * @return \Inertia\Response
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
