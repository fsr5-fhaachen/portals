<?php

namespace App\Http\Controllers;

use App\Helpers\Csv;
use App\Helpers\Price;
use App\Helpers\StrichlistensystemInvoicing;
use App\Helpers\Table\ArticlesTable;
use App\Helpers\Table\StrichlistensystemBillingTable;
use App\Helpers\Table\StrichlistensystemBookingsTable;
use App\Models\Article;
use App\Models\ArticleActionLog;
use App\Models\StrichlistensystemInvoice;
use App\Models\StrichlistensystemKioskDevice;
use App\Models\StrichlistensystemPayment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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
            // as list, an object with numeric keys would be sorted by the browser
            'vatRates' => collect(Article::VAT_RATES)->map(fn (string $label, int $rate) => ['value' => $rate, 'label' => $label])->values(),
        ]);
    }

    /**
     * Create a new article
     */
    public function storeArticle(Request $request): RedirectResponse
    {
        $validated = $this->storeArticleImage($this->validateArticle($request));

        try {
            $article = Article::create($validated);
        } catch (Throwable $exception) {
            $this->deleteUploadedArticleImage($validated);

            throw $exception;
        }

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

        try {
            $pricedBookingsCount = DB::transaction(function () use ($article, $validated) {
                // lock the article so that parallel bookings use either the old or the new price
                Article::whereKey($article->id)->lockForUpdate()->first();

                $article->update($validated);

                return $article->applyPriceToBookingsWithoutPrice();
            });
        } catch (Throwable $exception) {
            $this->deleteUploadedArticleImage($validated, $oldImage);

            throw $exception;
        }

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
     * Display every booking with person, article and time, optionally only of one person
     */
    public function bookings(Request $request): Response|JsonResponse
    {
        $bookingsTable = new StrichlistensystemBookingsTable($request);
        if ($bookingsTable->wantsChanges()) {
            return $bookingsTable->changesResponse();
        }

        return Inertia::render('Dashboard/Admin/Strichlistensystem/Bookings', [
            'bookings' => $bookingsTable->scroll(),
            'person' => $request->filled('user') ? User::find($request->integer('user'), ['id', 'firstname', 'lastname']) : null,
        ]);
    }

    /**
     * Get the balance and the payments of a user (for the payment dialog)
     */
    public function billingUser(User $user, StrichlistensystemInvoicing $invoicing): JsonResponse
    {
        $latestPayment = $user->latestStrichlistensystemPayment();

        return response()->json([
            'balance' => $user->strichlistensystemBalance(),
            // the address is shown to check it before an invoice is created
            'billing' => [
                'wants_bank_transfer' => (bool) $user->wants_bank_transfer,
                'details' => $user->billingDetails(),
                'problems' => $invoicing->problems($user),
                'preview' => $invoicing->preview($user),
                'invoices' => $user->strichlistensystemInvoices()
                    ->orderByDesc('sequence')
                    ->get()
                    ->map(fn (StrichlistensystemInvoice $invoice) => [
                        'id' => $invoice->id,
                        'number' => $invoice->number,
                        'issued_on' => $invoice->issued_on->toDateString(),
                        'amount_due' => $invoice->amount_due,
                        'is_cancelled' => $invoice->isCancelled(),
                    ]),
            ],
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
                    'method' => $payment->method,
                    'created_at' => $payment->created_at,
                    'deleted_at' => $payment->deleted_at,
                    'created_by' => $payment->createdBy?->only(['id', 'firstname', 'lastname']),
                    'is_undoable' => $payment->id === $latestPayment?->id,
                ]),
            // the labels of the payment methods, keyed by the method
            'payment_methods' => StrichlistensystemPayment::METHODS,
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
            'method' => ['required', Rule::in(array_keys(StrichlistensystemPayment::METHODS))],
        ], [
            'amount.min' => 'Der Betrag muss mindestens 0,01 € sein.',
            'amount.max' => 'Der Betrag darf den offenen Betrag von '.Price::format($open).' nicht übersteigen.',
            'method.required' => 'Bitte wähle die Zahlungsart.',
            'method.in' => 'Bitte wähle die Zahlungsart.',
        ]);

        $amount = (int) $validated['amount'];

        if (! $user->payStrichlistensystem($amount, $request->user(), $validated['method'])) {
            Session::flash('error', 'Die Zahlung konnte nicht gespeichert werden, da sich der offene Betrag geändert hat. Bitte prüfe den Betrag erneut.');

            return Redirect::back();
        }

        Session::flash('success', 'Die Zahlung von <strong>'.Price::format($amount).'</strong> ('.StrichlistensystemPayment::METHODS[$validated['method']].') für <strong>'.e($user->firstname.' '.$user->lastname).'</strong> wurde gespeichert.');

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
     * The paid amounts per payment method are appended, so the existing columns keep their position.
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
        foreach (StrichlistensystemPayment::METHODS as $methodLabel) {
            $columns[] = 'Bezahlt_'.$methodLabel;
        }

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

            // paid amounts (in cents) by user id and by user id and payment method
            $payments = [];
            $paymentsByMethod = [];
            StrichlistensystemPayment::selectRaw('user_id, method, SUM(amount) as paid')
                ->groupBy('user_id', 'method')
                ->get()
                ->each(function ($row) use (&$payments, &$paymentsByMethod) {
                    $payments[$row->user_id] = ($payments[$row->user_id] ?? 0) + (int) $row->paid;
                    $paymentsByMethod[$row->user_id][$row->method] = (int) $row->paid;
                });

            $file = fopen('php://output', 'w');

            // add BOM so that excel detects utf-8
            fwrite($file, Csv::BOM);
            fputcsv($file, $columns, ';');

            // the id makes the order unique, otherwise users with the same name can be skipped or repeated between the chunks
            User::orderBy('lastname')->orderBy('firstname')->orderBy('id')->chunk(500, function ($users) use ($file, $articles, $amounts, $totals, $payments, $paymentsByMethod) {
                foreach ($users as $user) {
                    $row = [Csv::escapeCell($user->lastname), Csv::escapeCell($user->firstname)];
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
                    foreach (array_keys(StrichlistensystemPayment::METHODS) as $method) {
                        $row[] = Price::toDecimal($paymentsByMethod[$user->id][$method] ?? 0);
                    }

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
     * Upload the image of an article to the s3 storage and replace the uploaded file in the validated data by its path.
     * Without a new image the image is kept, unless it should be removed.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     *
     * @throws ValidationException if the image could not be uploaded
     */
    private function storeArticleImage(array $validated): array
    {
        $image = $validated['image'] ?? null;
        $removeImage = $validated['remove_image'] ?? false;
        unset($validated['image'], $validated['remove_image']);

        if ($image instanceof UploadedFile) {
            // without "throw" the s3 disk returns false for a failed upload (e.g. s3 not configured), other errors are thrown
            try {
                $path = $image->store('articles', 's3');
            } catch (FilesystemException $exception) {
                report($exception);
                $path = false;
            }

            if ($path === false) {
                Log::error('The image of a strichlistensystem article could not be uploaded to s3.');

                throw ValidationException::withMessages([
                    'image' => 'Das Bild konnte nicht gespeichert werden. Bitte versuche es erneut.',
                ]);
            }

            $validated['image'] = $path;
        } elseif ($removeImage) {
            $validated['image'] = null;
        }

        return $validated;
    }

    /**
     * Delete a newly uploaded article image if saving the article failed, so that no unreferenced file stays on s3.
     *
     * @param  array<string, mixed>  $validated
     */
    private function deleteUploadedArticleImage(array $validated, ?string $oldImage = null): void
    {
        $newImage = $validated['image'] ?? null;
        if (is_string($newImage) && $newImage !== $oldImage) {
            Storage::disk('s3')->delete($newImage);
        }
    }

    /**
     * Validate the request data of an article.
     *
     * @return array{name: string, icon: string, show_order: int, max_order_amount: int, price: int, vat_rate: int, is_active: bool, image?: UploadedFile|null, remove_image?: bool}
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
            // included in the gross price, it is stored with the items of the invoices
            'vat_rate' => ['required', 'integer', Rule::in(array_keys(Article::VAT_RATES))],
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
            'vat_rate.required' => 'Bitte wähle den Mehrwertsteuersatz.',
            'vat_rate.in' => 'Bitte wähle den Mehrwertsteuersatz.',
        ]);
    }
}
