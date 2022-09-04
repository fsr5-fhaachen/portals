<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get group_tutors for the group.
     *
     * @return HasMany
     */
    public function groupTutors()
    {
        return $this->hasMany(GroupTutor::class);
    }

    /**
     * Get registrations for the group.
     *
     * @return HasMany
     */
    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Get stops for the group.
     *
     * @return HasMany
     */
    public function stops()
    {
        return $this->hasMany(Stop::class);
    }

    /**
     * Get course for the group.
     *
     * @return BelongsTo
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get event for the group.
     *
     * @return BelongsTo
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get tutors for the group.
     *
     * @return BelongsToMany
     */
    public function tutors()
    {
        return $this->belongsToMany(User::class, 'group_tutor')->using(GroupTutor::class);
    }

    /**
     * Get stations for the group.
     *
     * @return BelongsToMany
     */
    public function stations()
    {
        return $this->belongsToMany(Station::class, 'stops')->using(Stop::class);
    }
}
