<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Invoice of the strichlistensystem. Everything that is printed on the invoice is stored with it (see the migration),
 * so an issued invoice never changes. The amounts are in cents.
 *
 * @property array{name: string, street: string, postal_code: string, city: string} $recipient
 * @property array<string, mixed> $issuer
 * @property list<array{name: string, quantity: int, unit_price: int, total: int, vat_rate?: int}> $items
 */
class StrichlistensystemInvoice extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'recipient' => 'array',
            'issuer' => 'array',
            'items' => 'array',
            'subtotal' => 'integer',
            'paid' => 'integer',
            'amount_due' => 'integer',
            'issued_on' => 'date',
            'due_on' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Get the user (recipient) of the invoice.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin that created the invoice.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the bookings of the invoice.
     */
    public function articleActionLogs(): HasMany
    {
        return $this->hasMany(ArticleActionLog::class, 'invoice_id');
    }

    /**
     * Check if the invoice was cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    /**
     * Check if the invoice shows the vat (snapshot of the settings), false for invoices without vat.
     */
    public function chargesVat(): bool
    {
        return ! empty($this->issuer['charges_vat']);
    }

    /**
     * Get the gross, net and vat amounts (in cents) per vat rate, the highest rate first. The prices of the items are
     * gross prices, the vat is included. Empty for invoices without vat.
     *
     * @return list<array{rate: int, gross: int, net: int, vat: int}>
     */
    public function vatBreakdown(): array
    {
        if (! $this->chargesVat()) {
            return [];
        }

        return collect($this->items)
            ->groupBy(fn (array $item) => max(0, (int) ($item['vat_rate'] ?? 0)))
            ->map(function ($items, int $rate) {
                $gross = (int) $items->sum('total');
                $vat = (int) round($gross * $rate / (100 + $rate));

                return ['rate' => $rate, 'gross' => $gross, 'net' => $gross - $vat, 'vat' => $vat];
            })
            ->sortByDesc('rate')
            ->values()
            ->all();
    }

    /**
     * Get the vat (in cents) that is included in the gross prices of the items.
     */
    public function vatAmount(): int
    {
        return array_sum(array_column($this->vatBreakdown(), 'vat'));
    }

    /**
     * Get the net amount (in cents) of the items, the sum without the included vat.
     */
    public function netAmount(): int
    {
        return $this->subtotal - $this->vatAmount();
    }

    /**
     * Get the file name of the pdf, e.g. "GER26-0001.pdf".
     */
    public function pdfFileName(): string
    {
        return preg_replace('/[^A-Za-z0-9._-]+/', '_', $this->number).'.pdf';
    }
}
