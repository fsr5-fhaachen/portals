<?php

namespace App\Http\Controllers;

use App\Helpers\Price;
use App\Helpers\Table\ArticlesTable;
use App\Helpers\Table\StrichlistensystemBillingTable;
use App\Models\Article;
use App\Models\ArticleActionLog;
use App\Models\StrichlistensystemKioskDevice;
use App\Models\StrichlistensystemPayment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardAdminStrichlistensystemController extends Controller
{
    /**
     * Display the article management
     */
    public function index(Request $request): Response|JsonResponse
    {
        $articlesTable = new ArticlesTable($request);
        if ($articlesTable->wantsChanges()) {
            return $articlesTable->changesResponse();
        }

        return Inertia::render('Dashboard/Admin/Strichlistensystem/Index', [
            'articles' => $articlesTable->scroll(),
            'icons' => config('strichlistensystem.icons'),
            'limitWindow' => config('strichlistensystem.self_service_limit_window'),
        ]);
    }

    /**
     * Create a new article
     */
    public function storeArticle(Request $request): RedirectResponse
    {
        $article = Article::create($this->storeArticleImage($this->validateArticle($request)));

        Session::flash('success', 'Der Artikel <strong>'.e($article->name).'</strong> wurde erstellt.');

        return Redirect::route('dashboard.admin.strichlistensystem.index');
    }

    /**
     * Update an article. A new price only applies to new bookings, except for bookings without a price
     * (booked before the article had a price), which get the price once.
     */
    public function updateArticle(Request $request, Article $article): RedirectResponse
    {
        $validated = $this->storeArticleImage($this->validateArticle($request, $article));
        $oldImage = $article->image;

        $pricedBookingsCount = DB::transaction(function () use ($article, $validated) {
            // lock the article so that parallel bookings use either the old or the new price
            Article::whereKey($article->id)->lockForUpdate()->first();

            $article->update($validated);

            return $article->applyPriceToBookingsWithoutPrice();
        });

        // the old image is only deleted after the article was saved with the new one
        if ($oldImage && $oldImage !== $article->image) {
            Storage::disk('s3')->delete($oldImage);
        }

        $message = 'Der Artikel <strong>'.e($article->name).'</strong> wurde gespeichert.';
        if ($pricedBookingsCount > 0) {
            $message .= ' '.$pricedBookingsCount.' '.($pricedBookingsCount === 1 ? 'Buchung' : 'Buchungen').' ohne Preis '.($pricedBookingsCount === 1 ? 'hat' : 'haben').' den Preis '.Price::format($article->price).' erhalten.';
        }

        Session::flash('success', $message);

        return Redirect::route('dashboard.admin.strichlistensystem.index');
    }

    /**
     * Activate or deactivate an article. Deactivated articles are hidden and can not be booked anymore.
     */
    public function toggleArticleActive(Article $article): RedirectResponse
    {
        $article->is_active = ! $article->is_active;
        $article->save();

        Session::flash('success', 'Der Artikel <strong>'.e($article->name).'</strong> wurde '.($article->is_active ? 'aktiviert' : 'deaktiviert').'.');

        return Redirect::route('dashboard.admin.strichlistensystem.index');
    }

    /**
     * Delete an article without bookings
     */
    public function deleteArticle(Article $article): RedirectResponse
    {
        if ($article->articleActionLogs()->withTrashed()->exists()) {
            Session::flash('error', 'Der Artikel <strong>'.e($article->name).'</strong> wurde bereits gebucht und kann deshalb nicht gelöscht werden.');

            return Redirect::route('dashboard.admin.strichlistensystem.index');
        }

        $article->delete();
        $article->deleteImage();

        Session::flash('success', 'Der Artikel <strong>'.e($article->name).'</strong> wurde gelöscht.');

        return Redirect::route('dashboard.admin.strichlistensystem.index');
    }

    /**
     * Display the balances of all users and the payments
     */
    public function billing(Request $request): Response|JsonResponse
    {
        $billingTable = new StrichlistensystemBillingTable($request);
        if ($billingTable->wantsChanges()) {
            return $billingTable->changesResponse();
        }

        return Inertia::render('Dashboard/Admin/Strichlistensystem/Billing', [
            'balances' => $billingTable->scroll(),
            'articlesWithoutPriceCount' => fn () => Article::whereNull('price')->count(),
        ]);
    }

    /**
     * Get the balance and the payments of a user (for the payment dialog)
     */
    public function billingUser(User $user): JsonResponse
    {
        $latestPayment = $user->latestStrichlistensystemPayment();

        return response()->json([
            'balance' => $user->strichlistensystemBalance(),
            'payments' => $user->strichlistensystemPayments()
                ->withTrashed()
                ->with('createdBy:id,firstname,lastname')
                ->latest()
                ->latest('id')
                ->limit(50)
                ->get()
                ->map(fn (StrichlistensystemPayment $payment) => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'created_at' => $payment->created_at,
                    'deleted_at' => $payment->deleted_at,
                    'created_by' => $payment->createdBy?->only(['id', 'firstname', 'lastname']),
                    'is_undoable' => $payment->id === $latestPayment?->id,
                ]),
        ]);
    }

    /**
     * Record a payment of a user. Redirects back to keep the search and sorting of the billing table.
     */
    public function storePayment(Request $request, User $user): RedirectResponse
    {
        $open = $user->strichlistensystemBalance()['open'];
        if ($open < 1) {
            Session::flash('error', '<strong>'.e($user->firstname.' '.$user->lastname).'</strong> hat keinen offenen Betrag.');

            return Redirect::back();
        }

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:'.$open],
        ], [
            'amount.min' => 'Der Betrag muss mindestens 0,01 € sein.',
            'amount.max' => 'Der Betrag darf den offenen Betrag von '.Price::format($open).' nicht übersteigen.',
        ]);

        $amount = (int) $validated['amount'];

        if (! $user->payStrichlistensystem($amount, $request->user())) {
            Session::flash('error', 'Die Zahlung konnte nicht gespeichert werden, da sich der offene Betrag geändert hat. Bitte prüfe den Betrag erneut.');

            return Redirect::back();
        }

        Session::flash('success', 'Die Zahlung von <strong>'.Price::format($amount).'</strong> für <strong>'.e($user->firstname.' '.$user->lastname).'</strong> wurde gespeichert.');

        return Redirect::back();
    }

    /**
     * Undo the latest payment of a user. Redirects back to keep the search and sorting of the billing table.
     */
    public function deletePayment(StrichlistensystemPayment $payment): RedirectResponse
    {
        $user = $payment->user;

        if (! $user->undoStrichlistensystemPayment($payment)) {
            Session::flash('error', 'Nur die letzte Zahlung kann rückgängig gemacht werden.');

            return Redirect::back();
        }

        Session::flash('success', 'Die Zahlung von <strong>'.Price::format($payment->amount).'</strong> für <strong>'.e($user->firstname.' '.$user->lastname).'</strong> wurde rückgängig gemacht.');

        return Redirect::back();
    }

    /**
     * Download the bookings (without cancelled ones) and payments of all users as csv. The amounts use the
     * price of every booking at the time of the booking, so price changes do not change existing bookings.
     */
    public function export(): StreamedResponse
    {
        $fileName = 'strichlisten_export.csv';

        $headers = [
            'Content-type' => 'text/csv; charset=UTF-8',
            'Content-Encoding' => 'UTF-8',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $articles = Article::orderBy('show_order')->orderBy('name')->get();

        $columns = ['Nachname', 'Vorname'];
        foreach ($articles as $article) {
            $columns[] = 'Anz_'.$article->name;
        }
        foreach ($articles as $article) {
            $columns[] = 'Betrag_'.$article->name;
        }
        array_push($columns, 'Gesamt', 'Bezahlt', 'Offen');

        $callback = function () use ($columns, $articles) {
            // amounts and totals (in cents) by user id and article id
            $amounts = [];
            $totals = [];
            ArticleActionLog::selectRaw('user_id, article_id, count(*) as amount, COALESCE(SUM(price), 0) as total')
                ->groupBy('user_id', 'article_id')
                ->get()
                ->each(function ($row) use (&$amounts, &$totals) {
                    $amounts[$row->user_id][$row->article_id] = (int) $row->amount;
                    $totals[$row->user_id][$row->article_id] = (int) $row->total;
                });

            // paid amounts (in cents) by user id
            $payments = StrichlistensystemPayment::selectRaw('user_id, SUM(amount) as paid')
                ->groupBy('user_id')
                ->pluck('paid', 'user_id')
                ->map(fn ($paid) => (int) $paid);

            $file = fopen('php://output', 'w');

            // add BOM so that excel detects utf-8
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns, ';');

            // the id makes the order unique, otherwise users with the same name can be skipped or repeated between the chunks
            User::orderBy('lastname')->orderBy('firstname')->orderBy('id')->chunk(500, function ($users) use ($file, $articles, $amounts, $totals, $payments) {
                foreach ($users as $user) {
                    $row = [$this->escapeCsvCell($user->lastname), $this->escapeCsvCell($user->firstname)];
                    foreach ($articles as $article) {
                        $row[] = $amounts[$user->id][$article->id] ?? 0;
                    }

                    $total = 0;
                    foreach ($articles as $article) {
                        $articleTotal = $totals[$user->id][$article->id] ?? 0;
                        $total += $articleTotal;
                        $row[] = Price::toDecimal($articleTotal);
                    }

                    $paid = $payments[$user->id] ?? 0;
                    array_push($row, Price::toDecimal($total), Price::toDecimal($paid), Price::toDecimal($total - $paid));

                    fputcsv($file, $row, ';');
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Display the kiosk devices with their pairing urls and qr codes
     */
    public function kioskDevices(): Response
    {
        return Inertia::render('Dashboard/Admin/Strichlistensystem/KioskDevices', [
            'devices' => StrichlistensystemKioskDevice::orderBy('name')->get()->map(fn (StrichlistensystemKioskDevice $device) => [
                'id' => $device->id,
                'name' => $device->name,
                'last_used_at' => $device->last_used_at,
                'pairing_url' => $device->pairingUrl(),
                'pairing_qr_code' => $device->pairingQrCodeSvg(),
            ]),
        ]);
    }

    /**
     * Create a new kiosk device
     */
    public function storeKioskDevice(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:1', 'max:50', Rule::unique('strichlistensystem_kiosk_devices', 'name')],
        ]);

        $device = StrichlistensystemKioskDevice::create([
            'name' => $validated['name'],
            'token' => StrichlistensystemKioskDevice::generateToken(),
        ]);

        Session::flash('success', 'Das Gerät <strong>'.e($device->name).'</strong> wurde erstellt. Öffne die Pairing-URL auf dem Tablet, um es freizuschalten.');

        return Redirect::route('dashboard.admin.strichlistensystem.kioskDevices');
    }

    /**
     * Generate a new token for a kiosk device, the paired tablet is locked out until it is paired again
     */
    public function regenerateKioskDeviceToken(StrichlistensystemKioskDevice $device): RedirectResponse
    {
        $device->update(['token' => StrichlistensystemKioskDevice::generateToken()]);

        Session::flash('success', 'Für das Gerät <strong>'.e($device->name).'</strong> wurde eine neue Pairing-URL erzeugt. Das Tablet muss neu freigeschaltet werden.');

        return Redirect::route('dashboard.admin.strichlistensystem.kioskDevices');
    }

    /**
     * Delete a kiosk device, the paired tablet is locked out
     */
    public function deleteKioskDevice(StrichlistensystemKioskDevice $device): RedirectResponse
    {
        $device->delete();

        Session::flash('success', 'Das Gerät <strong>'.e($device->name).'</strong> wurde gelöscht.');

        return Redirect::route('dashboard.admin.strichlistensystem.kioskDevices');
    }

    /**
     * Escape a text cell of the csv export. Spreadsheet programs run cells that start with =, +, -, @, tab
     * or carriage return as formula, a leading ' makes them plain text. Only used for free text like names.
     */
    private function escapeCsvCell(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * Upload the image of an article to the s3 storage and replace the uploaded file in the validated data by its path.
     * Without a new image the image is kept, unless it should be removed.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function storeArticleImage(array $validated): array
    {
        $image = $validated['image'] ?? null;
        $removeImage = $validated['remove_image'] ?? false;
        unset($validated['image'], $validated['remove_image']);

        if ($image instanceof UploadedFile) {
            $validated['image'] = $image->store('articles', 's3');
        } elseif ($removeImage) {
            $validated['image'] = null;
        }

        return $validated;
    }

    /**
     * Validate the request data of an article.
     *
     * @return array{name: string, icon: string, show_order: int, max_order_amount: int, price: int, is_active: bool, image?: UploadedFile|null, remove_image?: bool}
     */
    private function validateArticle(Request $request, ?Article $article = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'min:1', 'max:50', Rule::unique('articles', 'name')->ignore($article?->id)],
            'icon' => ['required', 'string', Rule::in(config('strichlistensystem.icons'))],
            'show_order' => ['required', 'integer', 'min:0', 'max:10000'],
            'max_order_amount' => ['required', 'integer', 'min:1', 'max:100'],
            // price in cents
            'price' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['required', 'boolean'],
            // no svg, it can contain scripts. png keeps the transparency for the light and dark mode
            'image' => ['nullable', File::types(['png', 'jpg', 'jpeg', 'webp'])->max(2 * 1024)],
            'remove_image' => ['boolean'],
        ], [
            'image.mimes' => 'Das Bild muss eine PNG-, JPG- oder WebP-Datei sein.',
            'image.max' => 'Das Bild darf höchstens 2 MB groß sein.',
            'price.required' => 'Bitte gib einen Preis an.',
            'price.min' => 'Der Preis darf nicht negativ sein.',
            'price.max' => 'Der Preis darf höchstens 1000,00 € sein.',
        ]);
    }
}
