<?php

namespace App\Http\Controllers;

use App\Helpers\ArticleBooking;
use App\Helpers\StorageImage;
use App\Models\Article;
use App\Models\ArticleActionLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StrichlistensystemController extends Controller
{
    /**
     * Display the strichlistensystem of the logged in user
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Strichlistensystem/Index', [
            'hasPin' => $user->hasPin(),
            'isDisabled' => (bool) $user->is_disabled,
            'articles' => $user->bookableArticles($this->limitSince(), config('strichlistensystem.self_service_limit_window')),
            'articleActionLogs' => $user->latestArticleActionLogs(),
            'balance' => $user->strichlistensystemBalance(),
            'limitWindow' => config('strichlistensystem.self_service_limit_window'),
            // the name is suggested from the first and last name until the user saved an own one
            'billingDetails' => [
                'wants_bank_transfer' => (bool) $user->wants_bank_transfer,
                'name' => $user->billing_name ?: $user->suggestedBillingName(),
                'street' => (string) $user->billing_street,
                'postal_code' => (string) $user->billing_postal_code,
                'city' => (string) $user->billing_city,
            ],
        ]);
    }

    /**
     * Save whether the logged in user wants to pay by bank transfer and the address for the invoice
     */
    public function updateBillingDetails(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'wants_bank_transfer' => ['required', 'boolean'],
            'name' => ['exclude_unless:wants_bank_transfer,true', 'required', 'string', 'max:70'],
            'street' => ['exclude_unless:wants_bank_transfer,true', 'required', 'string', 'max:100'],
            'postal_code' => ['exclude_unless:wants_bank_transfer,true', 'required', 'string', 'max:20'],
            'city' => ['exclude_unless:wants_bank_transfer,true', 'required', 'string', 'max:100'],
        ], [
            'name.required' => 'Bitte gib deinen vollständigen Namen an.',
            'street.required' => 'Bitte gib deine Straße und Hausnummer an.',
            'postal_code.required' => 'Bitte gib deine Postleitzahl an.',
            'city.required' => 'Bitte gib deinen Ort an.',
        ]);

        // load a fresh instance, because HandleInertiaRequests adds non-database attributes (rolesArray, permissionsArray) to the authenticated user
        $user = User::findOrFail($request->user()->id);

        $user->wants_bank_transfer = (bool) $validated['wants_bank_transfer'];
        // without bank transfer the address is kept, so it does not have to be entered again
        if ($user->wants_bank_transfer) {
            $user->billing_name = trim($validated['name']);
            $user->billing_street = trim($validated['street']);
            $user->billing_postal_code = trim($validated['postal_code']);
            $user->billing_city = trim($validated['city']);
        }
        $user->save();

        Session::flash('success', $user->wants_bank_transfer
            ? 'Deine Rechnungsadresse wurde gespeichert. Die Orga kann dir jetzt eine Rechnung zum Überweisen erstellen.'
            : 'Du zahlst nicht per Überweisung.');

        return Redirect::route('strichlistensystem.index');
    }

    /**
     * Set or change the pin of the logged in user
     */
    public function updatePin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pin' => ['required', 'string', 'regex:/^\d{4}$/', 'confirmed', Rule::notIn(config('strichlistensystem.trivial_pins'))],
        ], [
            'pin.regex' => 'Die PIN muss aus genau 4 Ziffern bestehen.',
            'pin.confirmed' => 'Die PINs stimmen nicht überein.',
            'pin.not_in' => 'Diese PIN ist zu leicht zu erraten. Bitte wähle eine andere PIN.',
        ]);

        // load a fresh instance, because HandleInertiaRequests adds non-database attributes (rolesArray, permissionsArray) to the authenticated user
        $user = User::findOrFail($request->user()->id);
        $hadPin = $user->hasPin();

        $user->pin = $validated['pin'];
        $user->save();

        Session::flash('success', $hadPin ? 'Deine PIN wurde geändert.' : 'Deine PIN wurde gespeichert. Du kannst jetzt Striche machen.');

        return Redirect::route('strichlistensystem.index');
    }

    /**
     * Redirect to the image of an article in the s3 storage.
     */
    public function articleImage(Article $article): RedirectResponse
    {
        return StorageImage::redirect($article->image, 86400, true);
    }

    /**
     * Book an article for the logged in user
     */
    public function buy(Request $request, Article $article, ArticleBooking $articleBooking): RedirectResponse
    {
        $user = $request->user();

        if (! $user->canUseStrichlistensystem()) {
            Session::flash('error', 'Du kannst aktuell keine Striche machen.');

            return Redirect::route('strichlistensystem.index');
        }

        $limitWindow = config('strichlistensystem.self_service_limit_window');

        $error = $articleBooking->book(
            $request,
            $user,
            $article,
            $this->limitSince(),
            'Du hast die maximale Anzahl für <strong>'.e($article->name).'</strong> erreicht. Bitte warte bis zu '.$limitWindow.' Sekunden.',
            'Du kannst von '.$article->name.' innerhalb von '.$limitWindow.' Sekunden nur noch :max Stück buchen.',
        );
        if ($error !== null) {
            Session::flash('error', $error);

            return Redirect::route('strichlistensystem.index');
        }

        Session::flash('success', 'Du hast <strong>'.$request->integer('amount').'x '.e($article->name).'</strong> gebucht.');

        return Redirect::route('strichlistensystem.index');
    }

    /**
     * Cancel a booking of the logged in user
     */
    public function cancel(Request $request, ArticleActionLog $articleActionLog): RedirectResponse
    {
        if (! $request->user()->canUseStrichlistensystem()) {
            Session::flash('error', 'Du kannst aktuell keine Buchungen stornieren.');

            return Redirect::route('strichlistensystem.index');
        }

        if (! $request->user()->cancelArticleActionLog($articleActionLog)) {
            Session::flash('error', 'Die Buchung kann nicht mehr storniert werden.');

            return Redirect::route('strichlistensystem.index');
        }

        Session::flash('success', 'Die Buchung wurde storniert.');

        return Redirect::route('strichlistensystem.index');
    }

    /**
     * Get the start of the time window in which the article limit applies. Bookings that are exactly
     * "window" seconds old do not count anymore, so that the limit resets at first booking + window.
     */
    private function limitSince(): Carbon
    {
        return now()->subSeconds(config('strichlistensystem.self_service_limit_window'))->addSecond();
    }
}
