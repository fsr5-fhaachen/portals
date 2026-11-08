<?php

namespace App\Helpers;

use App\Models\Article;
use App\Models\ArticleActionLog;
use App\Models\State;
use App\Models\StrichlistensystemInvoice;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Creation and cancellation of the invoices of the strichlistensystem.
 *
 * An invoice contains all bookings (with a price, without cancelled ones) of a user that are not on another invoice yet,
 * grouped by article and price. Payments first cover the earlier invoices, the rest is deducted as "already paid", so
 * the amount due is never higher than the items of the invoice.
 */
class StrichlistensystemInvoicing
{
    public function __construct(private StrichlistensystemInvoiceSettings $settings) {}

    /**
     * Get the reasons why no invoice can be created for the user, empty if an invoice can be created.
     *
     * @return list<string>
     */
    public function problems(User $user): array
    {
        $problems = [];

        $missingSettings = $this->settings->missing();
        if ($missingSettings) {
            $problems[] = 'Die Rechnungseinstellungen sind unvollständig: '.implode(', ', $missingSettings).'.';
        }

        if (! $user->wants_bank_transfer) {
            $problems[] = 'Die Person hat nicht angegeben, dass sie per Überweisung zahlen möchte.';
        } elseif (! $user->hasCompleteBillingDetails()) {
            $problems[] = 'Die Rechnungsadresse der Person ist unvollständig.';
        }

        $preview = $this->preview($user);
        if (! $preview['items']) {
            $problems[] = 'Es gibt keine Buchungen, die noch nicht abgerechnet sind.';
        } elseif ($preview['amount_due'] < 1) {
            $problems[] = 'Die noch nicht abgerechneten Buchungen sind schon bezahlt.';
        }

        return $problems;
    }

    /**
     * Get the items and amounts of the next invoice of the user (amounts in cents).
     *
     * @return array{items: list<array{name: string, quantity: int, unit_price: int, total: int, vat_rate: int}>, subtotal: int, paid: int, amount_due: int}
     */
    public function preview(User $user): array
    {
        return $this->calculate($user, $this->invoiceableBookings($user)->get());
    }

    /**
     * Create the invoice of all bookings of the user that are not on an invoice yet.
     *
     * @throws DomainException if no invoice can be created (see problems())
     */
    public function create(User $user, User $admin): StrichlistensystemInvoice
    {
        return DB::transaction(function () use ($user, $admin) {
            // the state of the settings serializes the creation, so every number is only used once
            State::firstOrCreate(['key' => StrichlistensystemInvoiceSettings::KEY], ['value' => '{}']);
            State::where('key', StrichlistensystemInvoiceSettings::KEY)->lockForUpdate()->first();
            // parallel bookings and payments of the user wait until the invoice is created
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            $problems = $this->problems($user);
            if ($problems) {
                throw new DomainException(implode(' ', $problems));
            }

            $bookings = $this->invoiceableBookings($user)->lockForUpdate()->get();
            $amounts = $this->calculate($user, $bookings);
            $settings = $this->settings->settings();

            $sequence = max($settings['start_number'], (int) StrichlistensystemInvoice::max('sequence') + 1);
            $number = $this->settings->formatNumber($sequence);
            $issuedOn = now()->startOfDay();

            $invoice = StrichlistensystemInvoice::create([
                'sequence' => $sequence,
                'number' => $number,
                'user_id' => $user->id,
                'created_by_id' => $admin->id,
                'title' => $this->title($settings['title_template'], $user, $number, $issuedOn->format('d.m.Y')),
                'recipient' => $user->billingDetails(),
                'issuer' => [
                    'club_name' => $settings['club_name'],
                    'club_street' => $settings['club_street'],
                    'club_postal_code' => $settings['club_postal_code'],
                    'club_city' => $settings['club_city'],
                    'chairperson' => $settings['chairperson'],
                    'deputy_chairperson' => $settings['deputy_chairperson'],
                    'email' => $settings['email'],
                    'charges_vat' => $settings['charges_vat'],
                    'tax_number' => $settings['tax_number'],
                    'vat_id' => $settings['vat_id'],
                    'account_holder' => $this->settings->accountHolder(),
                    'iban' => $settings['iban'],
                    'bic' => $settings['bic'],
                    'bank_name' => $settings['bank_name'],
                    'note' => $settings['note'],
                ],
                'items' => $amounts['items'],
                'subtotal' => $amounts['subtotal'],
                'paid' => $amounts['paid'],
                'amount_due' => $amounts['amount_due'],
                'issued_on' => $issuedOn,
                'due_on' => $issuedOn->copy()->addDays(max(0, $settings['payment_term_days'])),
            ]);

            ArticleActionLog::whereKey($bookings->modelKeys())->update(['invoice_id' => $invoice->id]);

            return $invoice;
        });
    }

    /**
     * Cancel the invoice. Its bookings can be invoiced again, the number is never used again.
     */
    public function cancel(StrichlistensystemInvoice $invoice): bool
    {
        return DB::transaction(function () use ($invoice) {
            $invoice = StrichlistensystemInvoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if ($invoice->isCancelled()) {
                return false;
            }

            $invoice->update(['cancelled_at' => now()]);
            $invoice->articleActionLogs()->update(['invoice_id' => null]);

            return true;
        });
    }

    /**
     * Get the payment qr code of the invoice as svg, null without an amount due.
     */
    public static function paymentQrCode(StrichlistensystemInvoice $invoice): ?string
    {
        if ($invoice->amount_due < 1 || empty($invoice->issuer['iban'])) {
            return null;
        }

        return EpcQrCode::svg(
            $invoice->issuer['account_holder'] ?: $invoice->issuer['club_name'],
            $invoice->issuer['iban'],
            $invoice->issuer['bic'] ?? null,
            $invoice->amount_due,
            $invoice->number.' '.$invoice->recipient['name'],
        );
    }

    /**
     * Get the bookings of the user with a price that are not on an invoice yet (without cancelled ones).
     */
    private function invoiceableBookings(User $user): HasMany
    {
        return $user->articleActionLogs()->whereNull('invoice_id')->whereNotNull('price');
    }

    /**
     * Group the bookings by article and price and deduct the payments that are not used by earlier invoices. Every item
     * gets the vat rate of its article (0 if the club does not charge vat), so an issued invoice keeps its rates.
     *
     * @param  Collection<int, ArticleActionLog>  $bookings
     * @return array{items: list<array{name: string, quantity: int, unit_price: int, total: int, vat_rate: int}>, subtotal: int, paid: int, amount_due: int}
     */
    private function calculate(User $user, Collection $bookings): array
    {
        $articles = Article::whereKey($bookings->pluck('article_id')->unique()->all())->get(['id', 'name', 'show_order', 'vat_rate'])->keyBy('id');
        $chargesVat = $this->settings->settings()['charges_vat'];

        $items = $bookings
            ->groupBy(fn (ArticleActionLog $booking) => $booking->article_id.'-'.$booking->price)
            ->map(function (Collection $group) use ($articles, $chargesVat) {
                $booking = $group->first();
                $article = $articles->get($booking->article_id);

                return [
                    'name' => $article?->name ?? 'Artikel',
                    'quantity' => $group->count(),
                    'unit_price' => (int) $booking->price,
                    'total' => $group->count() * (int) $booking->price,
                    'vat_rate' => $chargesVat ? (int) ($article?->vat_rate ?? 0) : 0,
                    'show_order' => $article?->show_order ?? PHP_INT_MAX,
                ];
            })
            ->sortBy([['show_order', 'asc'], ['name', 'asc'], ['unit_price', 'asc']])
            ->map(fn (array $item) => array_diff_key($item, ['show_order' => true]))
            ->values()
            ->all();

        $subtotal = array_sum(array_column($items, 'total'));

        $payments = (int) $user->strichlistensystemPayments()->sum('amount');
        $earlierInvoices = (int) $user->strichlistensystemInvoices()->whereNull('cancelled_at')->sum('subtotal');
        $paid = min($subtotal, max(0, $payments - $earlierInvoices));

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'paid' => $paid,
            'amount_due' => $subtotal - $paid,
        ];
    }

    /**
     * Fill the placeholders of the title template.
     */
    private function title(string $template, User $user, string $number, string $date): string
    {
        $title = strtr($template, [
            '{vorname}' => (string) $user->firstname,
            '{nachname}' => (string) $user->lastname,
            '{name}' => (string) $user->billing_name,
            '{nummer}' => $number,
            '{datum}' => $date,
        ]);

        return mb_substr(trim($title), 0, 255);
    }
}
