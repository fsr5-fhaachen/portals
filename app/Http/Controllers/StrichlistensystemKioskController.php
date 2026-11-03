<?php

namespace App\Http\Controllers;

use App\Helpers\ArticleBooking;
use App\Helpers\QrCode;
use App\Helpers\Table\KioskPersonsTable;
use App\Models\Article;
use App\Models\ArticleActionLog;
use App\Models\Course;
use App\Models\StrichlistensystemKioskDevice;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StrichlistensystemKioskController extends Controller
{
    /**
     * Session key of the person that unlocked the kiosk with the pin.
     *
     * @var string
     */
    private const SESSION_KEY = 'strichlistensystem.kiosk';

    /**
     * Display all persons of the kiosk
     */
    public function index(Request $request): Response
    {
        // the overview always locks the kiosk again
        Session::forget(self::SESSION_KEY);

        return Inertia::render('Strichlistensystem/Kiosk/Index', [
            'persons' => (new KioskPersonsTable($request))->scroll(),
            // only courses with active persons are shown as filter
            'courses' => fn () => Course::whereHas('users', fn ($query) => $query->where('is_disabled', false))->orderBy('name')->get(),
            // persons without a pin set it in the self service on their phone, the qr code saves typing the url
            'selfServiceUrl' => fn () => route('strichlistensystem.index'),
            'selfServiceQrCode' => fn () => QrCode::svg(route('strichlistensystem.index')),
        ]);
    }

    /**
     * Unlock the kiosk for a person with the pin
     */
    public function verifyPin(Request $request, User $user): RedirectResponse
    {
        if ($user->is_disabled) {
            abort(404);
        }

        if (! $user->hasPin()) {
            return Redirect::back()->withErrors([
                'pin' => 'Du hast noch keine PIN gesetzt. Melde dich dafür mit deinem Handy unter '.route('strichlistensystem.index').' an.',
            ]);
        }

        // wrong pins of all persons at this device, so that common pins can not be tried for many persons
        $deviceRateLimiterKey = 'strichlistensystem-kiosk-device-pin:'.$request->attributes->get('kioskDevice')->id;
        if (RateLimiter::tooManyAttempts($deviceRateLimiterKey, config('strichlistensystem.kiosk_device_max_failed_pins'))) {
            return Redirect::back()->withErrors([
                'pin' => 'Zu viele falsche PINs an diesem Gerät. Das Gerät ist noch '.ceil(RateLimiter::availableIn($deviceRateLimiterKey) / 60).' Minuten gesperrt.',
            ]);
        }

        $rateLimiterKey = 'strichlistensystem-pin:'.$user->id;
        if (RateLimiter::tooManyAttempts($rateLimiterKey, config('strichlistensystem.pin_max_attempts'))) {
            return Redirect::back()->withErrors([
                'pin' => 'Zu viele falsche Versuche. Bitte warte '.RateLimiter::availableIn($rateLimiterKey).' Sekunden.',
            ]);
        }

        $pin = (string) $request->input('pin');
        if (! preg_match('/^\d{4}$/', $pin) || ! $user->isPinValid($pin)) {
            RateLimiter::hit($rateLimiterKey, 60);
            RateLimiter::hit($deviceRateLimiterKey, config('strichlistensystem.kiosk_device_lock_seconds'));

            return Redirect::back()->withErrors([
                'pin' => 'Die PIN ist falsch.',
            ]);
        }

        RateLimiter::clear($rateLimiterKey);

        Session::put(self::SESSION_KEY, [
            'user_id' => $user->id,
            'unlocked_at' => now()->timestamp,
            'last_activity' => now()->timestamp,
        ]);

        return Redirect::route('strichlistensystem.kiosk.show', ['user' => $user->id]);
    }

    /**
     * Display the bookings of the unlocked person
     */
    public function show(User $user): Response|RedirectResponse
    {
        if (! $this->isUnlockedFor($user)) {
            return $this->redirectToIndex();
        }

        return Inertia::render('Strichlistensystem/Kiosk/Show', [
            'person' => $this->toPerson($user),
            'articles' => $user->bookableArticles($this->unlockedAt()),
            'articleActionLogs' => $user->latestArticleActionLogs(),
            'balance' => $user->strichlistensystemBalance(),
            // sum of the bookings of the current kiosk login
            'loginTotal' => $user->articleActionLogsTotal($this->unlockedAt()),
            'sessionTimeout' => config('strichlistensystem.kiosk_session_timeout'),
        ]);
    }

    /**
     * Book an article for the unlocked person
     */
    public function buy(Request $request, User $user, Article $article, ArticleBooking $articleBooking): RedirectResponse
    {
        if (! $this->isUnlockedFor($user)) {
            return $this->redirectToIndex();
        }

        $error = $articleBooking->book(
            $request,
            $user,
            $article,
            $this->unlockedAt(),
            'Du hast die maximale Anzahl für <strong>'.e($article->name).'</strong> in dieser Anmeldung erreicht.',
            'Du kannst von '.$article->name.' in dieser Anmeldung nur noch :max Stück buchen.',
        );
        if ($error !== null) {
            Session::flash('error', $error);

            return Redirect::route('strichlistensystem.kiosk.show', ['user' => $user->id]);
        }

        Session::flash('success', 'Du hast <strong>'.$request->integer('amount').'x '.e($article->name).'</strong> gebucht.');

        return Redirect::route('strichlistensystem.kiosk.show', ['user' => $user->id]);
    }

    /**
     * Cancel a booking of the unlocked person
     */
    public function cancel(User $user, ArticleActionLog $articleActionLog): RedirectResponse
    {
        if (! $this->isUnlockedFor($user)) {
            return $this->redirectToIndex();
        }

        if (! $user->cancelArticleActionLog($articleActionLog)) {
            Session::flash('error', 'Die Buchung kann nicht mehr storniert werden.');
        } else {
            Session::flash('success', 'Die Buchung wurde storniert.');
        }

        return Redirect::route('strichlistensystem.kiosk.show', ['user' => $user->id]);
    }

    /**
     * Pair a tablet with a kiosk device. The token is stored in a long living cookie, so the tablet
     * can open the kiosk without the token in the url.
     */
    public function pair(Request $request, StrichlistensystemKioskDevice $device): RedirectResponse
    {
        if (! $device->isTokenValid((string) $request->query('token'))) {
            abort(404);
        }

        $cookie = Cookie::make(
            StrichlistensystemKioskDevice::COOKIE_NAME,
            $device->cookieValue(),
            StrichlistensystemKioskDevice::COOKIE_LIFETIME,
            '/strichlistensystem/kiosk',
        );

        Session::flash('success', 'Dieses Gerät ist jetzt als Kiosk <strong>'.e($device->name).'</strong> freigeschaltet.');

        return Redirect::route('strichlistensystem.kiosk.index')->withCookie($cookie);
    }

    /**
     * Lock the kiosk again
     */
    public function logout(): RedirectResponse
    {
        Session::forget(self::SESSION_KEY);

        return Redirect::route('strichlistensystem.kiosk.index');
    }

    /**
     * Stream the avatar of a person from the s3 storage
     */
    public function avatar(User $user): StreamedResponse
    {
        if ($user->is_disabled || ! $user->avatar || ! Storage::disk('s3')->exists($user->avatar)) {
            abort(404);
        }

        return Storage::disk('s3')->response($user->avatar, null, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Check if the kiosk is unlocked for the given person and refresh the activity timestamp.
     */
    private function isUnlockedFor(User $user): bool
    {
        $kioskSession = Session::get(self::SESSION_KEY);

        if (
            ! is_array($kioskSession)
            || ($kioskSession['user_id'] ?? null) !== $user->id
            || ! isset($kioskSession['unlocked_at'])
            || ($kioskSession['last_activity'] ?? 0) + config('strichlistensystem.kiosk_session_timeout') < now()->timestamp
            || ! $user->canUseStrichlistensystem()
        ) {
            Session::forget(self::SESSION_KEY);

            return false;
        }

        $kioskSession['last_activity'] = now()->timestamp;
        Session::put(self::SESSION_KEY, $kioskSession);

        return true;
    }

    /**
     * Get the time the kiosk was unlocked with the pin. The article limit counts all bookings since then.
     * Must only be called after isUnlockedFor() returned true.
     */
    private function unlockedAt(): Carbon
    {
        return Carbon::createFromTimestamp(Session::get(self::SESSION_KEY.'.unlocked_at'), config('app.timezone'));
    }

    /**
     * Redirect to the kiosk overview because the kiosk is locked.
     */
    private function redirectToIndex(): RedirectResponse
    {
        Session::flash('warning', 'Die Sitzung ist abgelaufen. Bitte gib deine PIN erneut ein.');

        return Redirect::route('strichlistensystem.kiosk.index');
    }

    /**
     * Get the data of a person that is visible in the kiosk.
     *
     * @return array{id: int, firstname: string, lastname: string, course: ?Course, avatar_url: ?string}
     */
    private function toPerson(User $user): array
    {
        return [
            'id' => $user->id,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'course' => $user->course,
            'avatar_url' => $user->avatar ? route('strichlistensystem.kiosk.avatar', ['user' => $user->id], false) : null,
        ];
    }
}
