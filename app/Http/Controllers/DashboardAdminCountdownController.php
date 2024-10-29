<?php

namespace App\Http\Controllers;

use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\Rule;

class DashboardAdminCountdownController extends Controller
{
    /**
     * Display the dashboard admin countdown page
     */
    public function index(): Response
    {
        // check if a state exists or we need to create a new one
        $state = State::where('key', 'countdown')->first();
        if (! $state) {
            $state = new State;
            $state->key = 'countdown';
            $state->value = json_encode([
                'state' => 'setup',
                'time' => [
                    'seconds' => 0,
                    'minutes' => 0,
                    'hours' => 0,
                ],
                'direction' => 'up',
            ]);
        }

        return Inertia::render('Dashboard/Admin/Countdown/Index', [
            'state' => json_decode($state->value),
        ]);
    }

    /**
     * Execute the dashboard admin countdown submit action
     */
    public function indexExecuteSubmit(): JsonResponse
    {
        // validate the request
        $request = Request::validate([
            'state' => ['required', 'in:setup,idle,running,stopped'],
            'time.seconds' => ['required', 'numeric', 'min:0', 'max:59'],
            'time.minutes' => ['required', 'numeric', 'min:0', 'max:59'],
            'time.hours' => ['required', 'numeric', 'min:0', 'max:23'],
            'direction' => ['required', 'in:up,down'],
        ]);

        // check if a state exists or we need to create a new one
        $state = State::where('key', 'countdown')->first();
        if (! $state) {
            $state = new State;
            $state->key = 'countdown';
        }

        // update the state
        $state->value = json_encode([
            'state' => $request['state'],
            'time' => $request['time'],
            'direction' => $request['direction'],
        ]);

        // save the state
        $state->save();

        // return success
        return response()->json([
            'success' => true,
        ]);
    }

    /*
     * Display the dashboard admin countdown display page
    */
    public function display(): Response
    {
        return Inertia::render('Dashboard/Admin/Countdown/Display');
    }
}
