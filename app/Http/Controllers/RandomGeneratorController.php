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

/**
 * Opt out of the random generator for the first-year students and (with an own setting) for the tutors.
 */
class RandomGeneratorController extends Controller
{
    /**
     * Display the opt out of the random generator for the logged in first-year student
     */
    public function index(Request $request, RandomGenerator $randomGenerator): Response|RedirectResponse
    {
        if (! $randomGenerator->isOptOutEnabled()) {
            return $this->redirectOptOutDisabled('dashboard.index');
        }

        return $this->renderParticipation($request, route('dashboard.randomGenerator.update', absolute: false));
    }

    /**
     * Update the participation of the logged in first-year student in the random generator
     */
    public function update(Request $request, RandomGenerator $randomGenerator): RedirectResponse
    {
        if (! $randomGenerator->isOptOutEnabled()) {
            return $this->redirectOptOutDisabled('dashboard.index');
        }

        return $this->updateParticipation($request, 'dashboard.randomGenerator.index');
    }

    /**
     * Display the opt out of the random generator for the logged in tutor
     */
    public function tutorIndex(Request $request, RandomGenerator $randomGenerator): Response|RedirectResponse
    {
        if (! $randomGenerator->isTutorOptOutEnabled()) {
            return $this->redirectOptOutDisabled('dashboard.tutor.index');
        }

        return $this->renderParticipation($request, route('dashboard.tutor.randomGenerator.update', absolute: false));
    }

    /**
     * Update the participation of the logged in tutor in the random generator
     */
    public function tutorUpdate(Request $request, RandomGenerator $randomGenerator): RedirectResponse
    {
        if (! $randomGenerator->isTutorOptOutEnabled()) {
            return $this->redirectOptOutDisabled('dashboard.tutor.index');
        }

        return $this->updateParticipation($request, 'dashboard.tutor.randomGenerator.index');
    }

    /**
     * Render the participation of the logged in user, the form is submitted to the given url
     */
    private function renderParticipation(Request $request, string $submitUrl): Response
    {
        return Inertia::render('Dashboard/RandomGenerator', [
            'isParticipating' => ! $request->user()->random_generator_opt_out,
            'submitUrl' => $submitUrl,
        ]);
    }

    /**
     * Update the participation of the logged in user and redirect to the given route
     */
    private function updateParticipation(Request $request, string $redirectRoute): RedirectResponse
    {
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

        return Redirect::route($redirectRoute);
    }

    /**
     * Redirect to the given dashboard route, because the opt out is not enabled
     */
    private function redirectOptOutDisabled(string $route): RedirectResponse
    {
        Session::flash('info', 'Die Einstellungen zum Zufallsgenerator sind aktuell nicht freigeschaltet.');

        return Redirect::route($route);
    }
}
