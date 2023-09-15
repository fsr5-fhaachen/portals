<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Group extends Model implements Auditable
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
     * Get group_tutors for the group.
     */
    public function groupTutors(): HasMany
    {
        return $this->hasMany(GroupTutor::class);
    }

    /**
     * Get registrations for the group.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class)->orderBy('queue_position');
    }

    /**
     * Get stops for the group.
     */
    public function stops(): HasMany
    {
        return $this->hasMany(Stop::class);
    }

    /**
     * Get course for the group.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get event for the group.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get tutors for the group.
     */
    public function tutors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_tutor')->using(GroupTutor::class);
    }

    /**
     * Get stations for the group.
     */
    public function stations(): BelongsToMany
    {
        return $this->belongsToMany(Station::class, 'stops')->using(Stop::class);
    }
}
