<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Station extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get stops for the station.
     */
    public function stops(): HasMany
    {
        return $this->hasMany(Stop::class);
    }

    /**
     * Get event for the station.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get tutors for the station.
     */
    public function tutors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'station_tutor')->using(StationTutor::class);
    }

    /**
     * Get groups for the station.
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'stops')->using(Stop::class);
    }
}
