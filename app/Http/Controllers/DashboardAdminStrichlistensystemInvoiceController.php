<?php

namespace App\Http\Controllers;

use App\Helpers\Price;
use App\Helpers\StrichlistensystemInvoicePdf;
use App\Helpers\StrichlistensystemInvoiceSettings;
use App\Helpers\StrichlistensystemInvoicing;
use App\Helpers\Table\StrichlistensystemInvoicesTable;
use App\Models\StrichlistensystemInvoice;
use App\Models\User;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class DashboardAdminStrichlistensystemInvoiceController extends Controller
{
    public function __construct(
        private StrichlistensystemInvoiceSettings $settings,
        private StrichlistensystemInvoicing $invoicing,
    ) {}

    /**
     * Display all invoices
     */
    public function index(Request $request): Response|JsonResponse
    {
        $invoicesTable = new StrichlistensystemInvoicesTable($request);
        if ($invoicesTable->wantsChanges()) {
            return $invoicesTable->changesResponse();
        }

        return Inertia::render('Dashboard/Admin/Strichlistensystem/Invoices', [
            'invoices' => $invoicesTable->scroll(),
            'missingSettings' => $this->settings->missing(),
        ]);
    }

    /**
     * Display the settings of the invoices
     */
    public function settings(): Response
    {
        $settings = $this->settings->settings();
        $nextSequence = max($settings['start_number'], (int) StrichlistensystemInvoice::max('sequence') + 1);

        return Inertia::render('Dashboard/Admin/Strichlistensystem/InvoiceSettings', [
            'settings' => $settings,
            'titlePlaceholders' => StrichlistensystemInvoiceSettings::TITLE_PLACEHOLDERS,
            'missingSettings' => $this->settings->missing(),
            'nextNumber' => $this->settings->formatNumber($nextSequence),
            'lastSequence' => (int) StrichlistensystemInvoice::max('sequence'),
        ]);
    }

    /**
     * Save the settings of the invoices
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'club_name' => ['nullable', 'string', 'max:70'],
            'club_street' => ['nullable', 'string', 'max:100'],
            'club_postal_code' => ['nullable', 'string', 'max:20'],
            'club_city' => ['nullable', 'string', 'max:100'],
            'chairperson' => ['nullable', 'string', 'max:100'],
            'deputy_chairperson' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:100'],
            // the vat rates are set per article, with vat the tax number or the vat id is required (see missing())
            'charges_vat' => ['required', 'boolean'],
            'tax_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9\/ -]*$/'],
            'vat_id' => ['nullable', 'string', 'regex:/^\s*[A-Za-z]{2}(\s*[A-Za-z0-9]){2,13}\s*$/'],
            'account_holder' => ['nullable', 'string', 'max:70'],
            'iban' => ['nullable', 'string', 'max:42', function (string $attribute, mixed $value, \Closure $fail) {
                if (! StrichlistensystemInvoiceSettings::isValidIban((string) $value)) {
                    $fail('Die IBAN ist ungültig.');
                }
            }],
            'bic' => ['nullable', 'string', 'regex:/^\s*[A-Za-z]{6}[A-Za-z0-9]{2}([A-Za-z0-9]{3})?\s*$/'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'number_prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9._\/-]*$/'],
            'number_digits' => ['required', 'integer', 'min:1', 'max:10'],
            'start_number' => ['required', 'integer', 'min:1', 'max:999999999'],
            'title_template' => ['nullable', 'string', 'max:200'],
            'payment_term_days' => ['required', 'integer', 'min:0', 'max:365'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'bic.regex' => 'Die BIC ist ungültig.',
            'tax_number.regex' => 'Die Steuernummer darf nur Ziffern, Leerzeichen, / und - enthalten, z. B. 201/123/45678.',
            'vat_id.regex' => 'Die Umsatzsteuer-ID ist ungültig, z. B. DE123456789.',
            'number_prefix.regex' => 'Der Präfix darf nur Buchstaben, Ziffern und . _ / - enthalten.',
        ]);

        $this->settings->save(array_map(fn (mixed $value) => is_string($value) ? trim($value) : ($value ?? ''), $validated));

        Session::flash('success', 'Die Rechnungseinstellungen wurden gespeichert.');

        return Redirect::route('dashboard.admin.strichlistensystem.invoiceSettings');
    }

    /**
     * Create the invoice of all bookings of the user that are not invoiced yet. Redirects back, the payment dialog
     * reloads its details afterwards.
     */
    public function store(Request $request, User $user): RedirectResponse
    {
        try {
            $invoice = $this->invoicing->create($user, $request->user());
        } catch (DomainException $exception) {
            Session::flash('error', e($exception->getMessage()));

            return Redirect::back();
        }

        Session::flash('success', 'Die Rechnung <strong>'.e($invoice->number).'</strong> über '.e(Price::format($invoice->amount_due)).' wurde erstellt.');

        return Redirect::back();
    }

    /**
     * Show the pdf of the invoice in the browser
     */
    public function pdf(StrichlistensystemInvoice $invoice): HttpResponse
    {
        return response(StrichlistensystemInvoicePdf::render($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$invoice->pdfFileName().'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Cancel the invoice, its bookings can be invoiced again
     */
    public function cancel(StrichlistensystemInvoice $invoice): RedirectResponse
    {
        if (! $this->invoicing->cancel($invoice)) {
            Session::flash('error', 'Die Rechnung <strong>'.e($invoice->number).'</strong> ist bereits storniert.');

            return Redirect::back();
        }

        Session::flash('success', 'Die Rechnung <strong>'.e($invoice->number).'</strong> wurde storniert. Die Buchungen können neu abgerechnet werden.');

        return Redirect::back();
    }
}
