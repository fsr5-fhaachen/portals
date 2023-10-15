<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardAdminRandomGeneratorController extends Controller
{
    /**
     * Display the dashboard admin random generator page
     */
    public function index(): Response
    {
        // get all users except tutors and admins
        $users = User::where('is_tutor', false)->where('is_admin', false)->orderBy('firstname')->get();

        // get all courses
        $courses = Course::all();

        return Inertia::render('Dashboard/Admin/RandomGenerator/Index', [
            'users' => $users,
            'courses' => $courses,
        ]);
    }

    /**
     * Execute the dashboard admin random generator submit action
     */
    public function indexExecuteSubmit(): JsonResponse
    {
        // validate the request
        $request = Request::validate([
            'state' => ['required', 'string', 'in:setup,idle,running,stopped'],
            'user_id' => ['integer', 'exists:users,id'],
        ]);

        // get the user
        $user = null;
        if ($request['state'] == 'stopped') {
            $user = User::select('id', 'firstname', 'lastname')->where('id', $request['user_id'])->get();

            // check if no user was found
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ]);
            }
        }

        // check if a state exists or we need to create a new one
        $state = State::where('key', 'randomGenerator')->first();
        if (! $state) {
            $state = new State();
            $state->key = 'randomGenerator';
        }

        // update the state
        $state->value = json_encode([
            'state' => $request['state'],
            'user' => $user,
        ]);

        // save the state
        $state->save();

        // return success
        return response()->json([
            'success' => true,
        ]);
    }
}
