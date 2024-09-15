<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Event extends Model implements Auditable
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
            'registration_from' => 'datetime',
            'registration_to' => 'datetime',
        ];
    }

    /**
     * Get groups for the event.
     */
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    /**
     * Get registrations for the event.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class)->orderBy('queue_position');
    }

    /**
     * Get slots for the event.
     */
    public function slots(): HasMany
    {
        return $this->hasMany(Slot::class);
    }

    /**
     * Get stations for the event.
     */
    public function stations(): HasMany
    {
        return $this->hasMany(Station::class);
    }

    /**
     * Get courses for the group.
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_event')->using(CourseEvent::class);
    }
}
