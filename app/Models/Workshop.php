<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Workshop extends Model implements Auditable
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
     * Get event for the workshop.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the sessions of the workshop, one per block in which the workshop takes place.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(WorkshopSession::class);
    }

    /**
     * Get the priorities of the users for the workshop.
     */
    public function priorities(): HasMany
    {
        return $this->hasMany(WorkshopPriority::class);
    }
}
