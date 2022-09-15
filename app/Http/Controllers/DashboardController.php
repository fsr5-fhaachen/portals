<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Session;

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
     * Login a tutor
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function loginTutor(Request $request)
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
     * @param Request $request
     *
     * @return \Inertia\Response
     */
    public function cmsPage(Request $request)
    {
        $page = Page::where('slug', $request->slug)->first();

        if (!$page) {
            return Inertia::render('Dashboard/404');
        }

        return Inertia::render('Dashboard/Page', [
            'page' => $page
        ]);
    }
}
