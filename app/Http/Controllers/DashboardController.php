<?php

namespace App\Http\Controllers;

use App\Helpers\AuditRecorder;
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
        $events = Event::orderBy('sort_order')->with('courses')->get();

        // get registrations of the user
        $registrations = Auth::user()->registrations;

        return Inertia::render('Dashboard/Index', [
            'events' => $events,
            'registrations' => $registrations,
        ]);
    }

    /**
     * Display the tutor login page
     */
    public function login(): Response
    {
        return Inertia::render('Dashboard/Tutor/Login');
    }

    /**
     * Login a tutor
     */
    public function loginTutor(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // check which password we need
        if ($user->hasRole(['admin', 'super admin'])) {
            $neededPassword = config('app.admin_password');
            $successMessage = 'Du wurdest als Admin angemeldet.';
            $auditEvent = 'adminLogin';
        } elseif ($user->hasRole(['esa', 'stage tutor', 'tutor'])) {
            $neededPassword = config('app.tutor_password');
            $successMessage = 'Du wurdest als Tutor angemeldet.';
            $auditEvent = 'tutorLogin';
        } else {
            Session::flash('error', 'Du hast keine Berechtigung, dich als Tutor anzumelden.');

            return redirect()->back();
        }

        if (! $neededPassword || ! hash_equals((string) $neededPassword, (string) $request->input('password'))) {
            AuditRecorder::record($user, $auditEvent.'Failed');
            Session::flash('error', 'Das Passwort ist falsch.');

            return redirect()->back();
        }

        session(['tutor' => true]);
        AuditRecorder::record($user, $auditEvent);
        Session::flash('success', $successMessage);
        $intendedUrl = session('url.intended');

        return redirect()->to($intendedUrl ?: route('dashboard.index'));
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
