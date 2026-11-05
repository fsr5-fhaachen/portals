<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
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
            'workshop_priority_count' => 'integer',
            'workshops_published_at' => 'datetime',
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
     * Get workshop blocks (time blocks) for the event ordered by time.
     */
    public function workshopBlocks(): HasMany
    {
        return $this->hasMany(WorkshopBlock::class)->orderBy('starts_at')->orderBy('id');
    }

    /**
     * Get workshops for the event.
     */
    public function workshops(): HasMany
    {
        return $this->hasMany(Workshop::class)->orderBy('name');
    }

    /**
     * Get workshop sessions for the event.
     */
    public function workshopSessions(): HasManyThrough
    {
        return $this->hasManyThrough(WorkshopSession::class, Workshop::class);
    }

    /**
     * Check if the users can see their workshops.
     */
    public function areWorkshopsPublished(): bool
    {
        return $this->workshops_published_at !== null;
    }

    /**
     * Get courses for the group.
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_event')->using(CourseEvent::class);
    }
}
