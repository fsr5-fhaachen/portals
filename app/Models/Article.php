<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Contracts\Auditable;

class Article extends Model implements Auditable
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
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'image_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'show_order' => 'integer',
            'max_order_amount' => 'integer',
            'price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope a query to only include active articles (visible and bookable).
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Get the url of the uploaded image. The path is part of the url, so browsers can cache the image
     * and load a new one as soon as it is replaced.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->image
            ? route('strichlistensystem.articleImage', ['article' => $this->id, 'v' => substr(md5($this->image), 0, 8)], false)
            : null);
    }

    /**
     * Delete the uploaded image from the s3 storage.
     */
    public function deleteImage(): void
    {
        if ($this->image) {
            Storage::disk('s3')->delete($this->image);
        }
    }

    /**
     * Get the action logs (bookings) for the article.
     */
    public function articleActionLogs(): HasMany
    {
        return $this->hasMany(ArticleActionLog::class);
    }

    /**
     * Give the bookings without a price (booked before the article had a price) the current price of the article.
     * Bookings with a price keep it, so that price changes only apply to new bookings.
     */
    public function applyPriceToBookingsWithoutPrice(): int
    {
        if ($this->price === null) {
            return 0;
        }

        return $this->articleActionLogs()
            ->withTrashed()
            ->whereNull('price')
            ->update(['price' => $this->price]);
    }
}
