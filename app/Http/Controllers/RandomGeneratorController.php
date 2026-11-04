<?php

namespace App\Http\Controllers;

use App\Helpers\RandomGenerator;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class RandomGeneratorController extends Controller
{
    /**
     * Display the opt out of the random generator for the logged in user
     */
    public function index(Request $request, RandomGenerator $randomGenerator): Response|RedirectResponse
    {
        if (! $randomGenerator->isOptOutEnabled()) {
            return $this->redirectOptOutDisabled();
        }

        return Inertia::render('Dashboard/RandomGenerator', [
            'isParticipating' => ! $request->user()->random_generator_opt_out,
        ]);
    }

    /**
     * Update the participation of the logged in user in the random generator
     */
    public function update(Request $request, RandomGenerator $randomGenerator): RedirectResponse
    {
        if (! $randomGenerator->isOptOutEnabled()) {
            return $this->redirectOptOutDisabled();
        }

        $validated = $request->validate([
            'is_participating' => ['required', 'boolean'],
        ]);

        // load a fresh instance, because HandleInertiaRequests adds non-database attributes (rolesArray, permissionsArray) to the authenticated user
        $user = User::findOrFail($request->user()->id);
        $user->random_generator_opt_out = ! $validated['is_participating'];
        $user->save();

        Session::flash('success', $validated['is_participating']
            ? 'Du nimmst am Zufallsgenerator teil.'
            : 'Du nimmst nicht mehr am Zufallsgenerator teil.');

        return Redirect::route('dashboard.randomGenerator.index');
    }

    /**
     * Redirect to the dashboard, because the opt out is not enabled
     */
    private function redirectOptOutDisabled(): RedirectResponse
    {
        Session::flash('info', 'Die Einstellungen zum Zufallsgenerator sind aktuell nicht freigeschaltet.');

        return Redirect::route('dashboard.index');
    }
}
