<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class ArticleActionLog extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Seconds the server accepts a cancellation after the cancel window to compensate for latency.
     *
     * @var int
     */
    public const CANCEL_GRACE_SECONDS = 10;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'ip',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'cancel_until',
    ];

    /**
     * Get the user (buyer) of the action log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the article of the action log.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Get the time until the action log can be cancelled.
     */
    public function getCancelUntilAttribute(): ?Carbon
    {
        return $this->created_at?->copy()->addSeconds(config('strichlistensystem.cancel_window'));
    }

    /**
     * Check if the action log can still be cancelled.
     */
    public function isCancellable(): bool
    {
        if ($this->trashed() || ! $this->created_at) {
            return false;
        }

        return now()->lessThanOrEqualTo($this->cancel_until->copy()->addSeconds(self::CANCEL_GRACE_SECONDS));
    }
}
