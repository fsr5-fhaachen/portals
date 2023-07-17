<?php

namespace App\Http\Controllers;

use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use App\Models\Course;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class AppController extends Controller
{
    /**
     * Display the index page
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index(): RedirectResponse
    {
        // check if registration module is active
        if (Module::where('key', 'registration')->first()->active) {
            return Redirect::route('app.register');
        }

        return Redirect::route('app.login');
    }

    /**
     * Display the login page
     *
     * @return \Inertia\Response
     */
    public function login(): Response
    {
        return Inertia::render('Login');
    }

    /**
     * Display the register page
     *
     * @return \Inertia\Response
     */
    public function register(): Response
    {
        // get courses ordered by name
        $courses = Course::orderBy('name')->get();

        return Inertia::render('Register', [
            'courses' => $courses,
        ]);
    }

    /**
     * Display the 404 page
     *
     * @return \Inertia\Response
     */
    public function notFound(): Response
    {
        return Inertia::render('404');
    }

    /**
     * Register a new user
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function registerUser(): RedirectResponse
    {
        // check if user with email already exists and login
        $user = User::where('email', Request::input('email'))->first();
        if ($user) {
            Session::flash('info', 'Wir haben dich bereits in unserer Datenbank gefunden und haben dich automatisch eingeloggt.');

            return $this->authenticate($user);
        }

        // validate the request
        $validated = Request::validate([
            'firstname' => ['required', 'string', 'min:2', 'max:255'],
            'lastname' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'min:3', 'max:255', 'unique:users'],
            'email_confirm' => ['required', 'string', 'email', 'min:3', 'max:255', 'same:email'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ]);

        // remove email_confirm from array
        unset($validated['email_confirm']);

        // create the user
        $user = User::create($validated);

        return $this->authenticate($user);
    }

    /**
     * Login an existing user
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function loginUser(): RedirectResponse
    {
        // validate the request
        $validated = Request::validate([
            'email' => ['required', 'string', 'email', 'min:3', 'max:255'],
        ]);

        // check if user exists
        $user = User::where('email', $validated['email'])->first();
        if (! $user) {
            Session::flash('error', 'Es konnte kein Benutzer mit dieser E-Mail-Adresse gefunden werden.');

            return Redirect::back();
        }

        return $this->authenticate($user);
    }

    /**
     * Authenticate a user
     *
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function authenticate(User $user): RedirectResponse
    {
        // login the user
        Auth::login($user, true);

        return Redirect::route('dashboard.index');
    }
}
