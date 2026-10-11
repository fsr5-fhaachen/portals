<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable
{
    use HasFactory;
    use HasRoles;
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'remember_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'pin',
    ];

    /**
     * Attributes to exclude from the audit.
     *
     * @var array
     */
    protected $auditExclude = [
        'pin',
    ];

    /**
     * Get station_tutors for the user.
     */
    public function stationTutors(): HasMany
    {
        return $this->hasMany(StationTutor::class);
    }

    /**
     * Get group_tutors for the user.
     */
    public function groupTutors(): HasMany
    {
        return $this->hasMany(GroupTutor::class);
    }

    /**
     * Get registrations for the user.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class)->orderBy('queue_position');
    }

    /**
     * Get course for the user.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get article action logs (bookings of the strichlistensystem) for the user.
     */
    public function articleActionLogs(): HasMany
    {
        return $this->hasMany(ArticleActionLog::class);
    }

    /**
     * Check if the user has set a pin for the strichlistensystem.
     */
    public function hasPin(): bool
    {
        return $this->pin !== null && $this->pin !== '';
    }

    /**
     * Check if the given pin matches the pin of the user.
     */
    public function isPinValid(string $pin): bool
    {
        return $this->hasPin() && hash_equals((string) $this->pin, $pin);
    }

    /**
     * Get the amount of the article the user can still book. The max order amount is the limit for all
     * bookings (without cancelled ones) since the given time (kiosk login or start of the self service time window).
     */
    public function remainingArticleAmount(Article $article, CarbonInterface $limitSince): int
    {
        $bookedAmount = $this->articleActionLogs()
            ->where('article_id', $article->id)
            ->where('created_at', '>=', $limitSince)
            ->count();

        return max(0, $article->max_order_amount - $bookedAmount);
    }

    /**
     * Get the active articles with the amount the user can still book since the given time (remaining_amount).
     * If a time window is given, limit_resets_at contains the time when an exhausted article can be booked again.
     *
     * @return Collection<int, Article>
     */
    public function bookableArticles(CarbonInterface $limitSince, ?int $limitWindowSeconds = null): Collection
    {
        $bookings = $this->articleActionLogs()
            ->where('created_at', '>=', $limitSince)
            ->selectRaw('article_id, count(*) as amount, min(created_at) as first_booked_at')
            ->groupBy('article_id')
            ->get()
            ->keyBy('article_id');

        return Article::active()
            ->orderBy('show_order')
            ->orderBy('name')
            ->get()
            ->each(function (Article $article) use ($bookings, $limitWindowSeconds) {
                $booking = $bookings->get($article->id);

                $article->remaining_amount = max(0, $article->max_order_amount - (int) ($booking->amount ?? 0));

                // the oldest booking leaves the time window first, so at least one article is bookable again afterwards
                $article->limit_resets_at = $limitWindowSeconds !== null && $article->remaining_amount < 1 && $booking
                    ? Carbon::parse($booking->first_booked_at)->addSeconds($limitWindowSeconds)->toIso8601String()
                    : null;
            });
    }

    /**
     * Book an article the given amount of times for the user. Returns false if the limit since the given time would be exceeded.
     */
    public function buyArticle(Article $article, int $amount, ?string $ip, CarbonInterface $limitSince): bool
    {
        return DB::transaction(function () use ($article, $amount, $ip, $limitSince) {
            // lock the user so that parallel bookings (e.g. double clicks) can not exceed the limit
            self::whereKey($this->id)->lockForUpdate()->first();

            if ($amount < 1 || $amount > $this->remainingArticleAmount($article, $limitSince)) {
                return false;
            }

            for ($i = 0; $i < $amount; $i++) {
                $this->articleActionLogs()->create([
                    'article_id' => $article->id,
                    'ip' => $ip,
                ]);
            }

            return true;
        });
    }

    /**
     * Cancel an article action log of the user if it is still cancellable.
     */
    public function cancelArticleActionLog(ArticleActionLog $articleActionLog): bool
    {
        if ($articleActionLog->user_id !== $this->id || ! $articleActionLog->isCancellable()) {
            return false;
        }

        return (bool) $articleActionLog->delete();
    }

    /**
     * Check if the user is allowed to book articles in the strichlistensystem.
     */
    public function canUseStrichlistensystem(): bool
    {
        return ! $this->is_disabled && $this->hasPin();
    }

    /**
     * Get the latest article action logs (including cancelled ones) of the user.
     *
     * @return Collection<int, ArticleActionLog>
     */
    public function latestArticleActionLogs(int $limit = 20): Collection
    {
        return $this->articleActionLogs()
            ->withTrashed()
            ->with('article')
            ->latest()
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Get avatar for the user.
     */
    public function avatarUrl(): ?string
    {
        // check if avatar is set and file exists
        if ($this->avatar && Storage::disk('s3')->exists($this->avatar)) {
            // create presigned url
            return Storage::disk('s3')->temporaryUrl(
                $this->avatar,
                now()->addMinutes(60)
            );
        }

        return null;
    }

    /**
     * Signed avatar URL without a live S3 existence check. Safe for bulk
     * listings (avoids one S3 request per user); the app always clears the
     * `avatar` column when it deletes the file from S3, so a stale reference
     * should not occur in normal operation.
     */
    public function avatarUrlUnchecked(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        return Storage::disk('s3')->temporaryUrl(
            $this->avatar,
            now()->addMinutes(60)
        );
    }
}
