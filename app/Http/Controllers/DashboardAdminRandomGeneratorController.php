<?php

namespace App\Http\Controllers;

use App\Helpers\RandomGenerator;
use App\Models\Course;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class DashboardAdminRandomGeneratorController extends Controller
{
    /**
     * Display the dashboard admin random generator page
     */
    public function index(RandomGenerator $randomGenerator): Response
    {
        // get the participants of the selected group (first-year students or tutors)
        $users = $randomGenerator->participants()->orderBy('firstname')->with('course')->get()->map(function ($user) {
            $user->avatarUrl = $user->avatarUrlUnchecked();

            return $user;
        });

        // get all courses
        $courses = Course::all();

        $state = State::where('key', 'randomGenerator')->value('value');

        return Inertia::render('Dashboard/Admin/RandomGenerator/Index', [
            'users' => $users,
            'courses' => $courses,
            'settings' => $randomGenerator->settings(),
            'poolCount' => $randomGenerator->pool()->count(),
            'state' => $state ? json_decode($state) : ['state' => 'setup'],
        ]);
    }

    /**
     * Execute the dashboard admin random generator submit action
     */
    public function indexExecuteSubmit(RandomGenerator $randomGenerator): JsonResponse
    {
        // validate the request
        $request = Request::validate([
            'state' => ['required', 'string', 'in:setup,idle,running,stopped'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        // the display only shows the users of the pool while running
        $userIds = $request['state'] == 'running' ? $randomGenerator->pool()->pluck('id')->all() : null;

        // draw the user, a preselected user overrides the pool
        $user = null;
        if ($request['state'] == 'stopped') {
            $preselectedUser = isset($request['user_id']) ? User::find($request['user_id']) : null;
            $user = $randomGenerator->draw($preselectedUser);

            // check if no user was found
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Es ist niemand mehr im Lostopf.',
                ]);
            }

            $user->avatarUrl = $user->avatarUrl();
        }

        // check if a state exists or we need to create a new one
        $state = State::where('key', 'randomGenerator')->first();
        if (! $state) {
            $state = new State;
            $state->key = 'randomGenerator';
        }

        // update the state
        $state->value = json_encode([
            'state' => $request['state'],
            // return only id, firstname, lastname and avatarUrl
            'user' => $user ? $user->only(['id', 'firstname', 'lastname', 'avatarUrl']) : null,
            'userIds' => $userIds,
        ]);

        // save the state
        $state->save();

        // return success
        return response()->json([
            'success' => true,
            'user' => $user ? $user->only(['id', 'firstname', 'lastname']) : null,
        ]);
    }

    /**
     * Update the settings of the random generator
     */
    public function updateSettings(RandomGenerator $randomGenerator): RedirectResponse
    {
        $validated = Request::validate([
            'participants' => ['required', 'string', 'in:'.RandomGenerator::PARTICIPANTS_FIRST_YEARS.','.RandomGenerator::PARTICIPANTS_TUTORS],
            'course_ids' => ['present', 'array'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
            'opt_out_enabled' => ['required', 'boolean'],
            'tutor_opt_out_enabled' => ['required', 'boolean'],
            'hide_opted_out' => ['required', 'boolean'],
            'hide_other_courses' => ['required', 'boolean'],
            'hide_drawn' => ['required', 'boolean'],
        ]);

        $randomGenerator->saveSettings([
            'participants' => $validated['participants'],
            'course_ids' => array_values(array_unique(array_map('intval', $validated['course_ids']))),
            'opt_out_enabled' => (bool) $validated['opt_out_enabled'],
            'tutor_opt_out_enabled' => (bool) $validated['tutor_opt_out_enabled'],
            'hide_opted_out' => (bool) $validated['hide_opted_out'],
            'hide_other_courses' => (bool) $validated['hide_other_courses'],
            'hide_drawn' => (bool) $validated['hide_drawn'],
        ]);

        return Redirect::route('dashboard.admin.randomGenerator.index');
    }

    /**
     * Reset the drawn state of all users
     */
    public function resetDrawn(RandomGenerator $randomGenerator): RedirectResponse
    {
        $randomGenerator->resetDrawn();

        Session::flash('success', 'Alle Ziehungen wurden zurückgesetzt.');

        return Redirect::route('dashboard.admin.randomGenerator.index');
    }

    /**
     * Reset the drawn state of a user
     */
    public function resetDrawnUser(User $user): RedirectResponse
    {
        $user->random_generator_drawn_at = null;
        $user->save();

        Session::flash('success', '<strong>'.e($user->firstname.' '.$user->lastname).'</strong> kann wieder gezogen werden.');

        return Redirect::route('dashboard.admin.randomGenerator.index');
    }

    /*
     * Display the dashboard admin random generator display page
    */
    public function display(RandomGenerator $randomGenerator): Response
    {
        // get the users of both groups (without users that opted out), because the display is not reloaded when the
        // group changes, the running state contains the ids of the pool
        $users = $randomGenerator->candidates()->where('random_generator_opt_out', false)->get()->map(function ($user) {
            $user->avatarUrl = $user->avatarUrlUnchecked();

            // the display is public, so only the shown attributes are sent
            return $user->only(['id', 'firstname', 'lastname', 'avatarUrl']);
        });

        // shuffle the users
        $users = $users->shuffle()->values();

        return Inertia::render('Dashboard/Admin/RandomGenerator/Display', [
            'users' => $users,
        ]);
    }
}
