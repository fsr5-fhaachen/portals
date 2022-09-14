<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Foundation\Auth\User as Authenticatable;

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
        'remember_token'
    ];

    /**
     * Get station_tutors for the user.
     *
     * @return HasMany
     */
    public function stationTutors()
    {
        return $this->hasMany(StationTutor::class);
    }

    /**
     * Get group_tutors for the user.
     *
     * @return HasMany
     */
    public function groupTutors()
    {
        return $this->hasMany(GroupTutor::class);
    }

    /**
     * Get registrations for the user.
     *
     * @return HasMany
     */
    public function registrations()
    {
        return $this->hasMany(Registration::class)->orderBy('queue_position');
    }

    /**
     * Get course for the user.
     *
     * @return BelongsTo
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
