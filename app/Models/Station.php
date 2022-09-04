<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Station extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get stops for the station.
     *
     * @return HasMany
     */
    public function stops()
    {
        return $this->hasMany(Stop::class);
    }

    /**
     * Get event for the station.
     *
     * @return BelongsTo
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get tutors for the station.
     *
     * @return BelongsToMany
     */
    public function tutors()
    {
        return $this->belongsToMany(User::class, 'station_tutor')->using(StationTutor::class);
    }

    /**
     * Get groups for the station.
     *
     * @return BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'stops')->using(Stop::class);
    }
}
