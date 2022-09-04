<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Event extends Model implements Auditable
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
     * Get groups for the event.
     *
     * @return HasMany
     */
    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    /**
     * Get registrations for the event.
     *
     * @return HasMany
     */
    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Get slots for the event.
     *
     * @return HasMany
     */
    public function slots()
    {
        return $this->hasMany(Slot::class);
    }

    /**
     * Get stations for the event.
     *
     * @return HasMany
     */
    public function stations()
    {
        return $this->hasMany(Station::class);
    }
}
