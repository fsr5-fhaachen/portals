<?php

namespace App\Http\Controllers;

use App\Helpers\ArticleBooking;
use App\Models\Article;
use App\Models\ArticleActionLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        ]);
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
     * Stream the image of an article from the s3 storage. The url contains a version of the image,
     * so the image can be cached for a long time.
     */
    public function articleImage(Article $article): StreamedResponse
    {
        if (! $article->image || ! Storage::disk('s3')->exists($article->image)) {
            abort(404);
        }

        return Storage::disk('s3')->response($article->image, null, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
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
