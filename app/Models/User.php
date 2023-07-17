<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use OwenIt\Auditing\Contracts\Auditable;

class User extends Authenticatable implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'remember_token',
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
}
