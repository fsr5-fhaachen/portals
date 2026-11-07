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
 * @property list<array{name: string, quantity: int, unit_price: int, total: int}> $items
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
     * Get the file name of the pdf, e.g. "GER26-0001.pdf".
     */
    public function pdfFileName(): string
    {
        return preg_replace('/[^A-Za-z0-9._-]+/', '_', $this->number).'.pdf';
    }
}
