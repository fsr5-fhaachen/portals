<?php

namespace App\Http\Controllers;

use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardAdminScoreSystemController extends Controller
{
    /**
     * Maximum amount of teams, more do not fit next to each other on the display.
     */
    public const MAXIMUM_TEAM_AMOUNT = 8;

    /**
     * Display the dashboard admin score system page
     */
    public function index(): Response
    {
        // check if a state exists or we need to create a new one
        $state = State::where('key', 'scoreSystem')->first();
        if (! $state) {
            $state = new State;
            $state->key = 'scoreSystem';
        }

        return Inertia::render('Dashboard/Admin/ScoreSystem/Index', [
            'state' => json_decode($state->value),
        ]);
    }

    /**
     * Execute the dashboard admin score system submit action
     */
    public function indexExecuteSubmit(): JsonResponse
    {
        // validate the request
        $request = Request::validate([
            'teams' => ['required', 'array', 'min:1', 'max:'.self::MAXIMUM_TEAM_AMOUNT],
            'teams.*.name' => ['required', 'string'],
            'teams.*.score' => ['required', 'string'],
        ]);

        // check if a state exists or we need to create a new one
        $state = State::where('key', 'scoreSystem')->first();
        if (! $state) {
            $state = new State;
            $state->key = 'scoreSystem';
        }

        // update the state
        $state->value = json_encode([
            'teams' => $request['teams'],
        ]);

        // save the state
        $state->save();

        // return success
        return response()->json([
            'success' => true,
        ]);
    }

    /*
     * Display the dashboard admin score system display page
    */
    public function display(): Response
    {
        return Inertia::render('Dashboard/Admin/ScoreSystem/Display');
    }
}
