<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Registration extends Model implements Auditable
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
            'form_responses' => 'array',
        ];
    }

    /**
     * Get event for the registration.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get user for the registration.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get slot for the registration.
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }

    /**
     * Get the workshop priorities of the registration ordered by priority.
     */
    public function workshopPriorities(): HasMany
    {
        return $this->hasMany(WorkshopPriority::class)->orderBy('priority');
    }

    /**
     * Get the workshop assignments of the registration.
     */
    public function workshopAssignments(): HasMany
    {
        return $this->hasMany(WorkshopAssignment::class);
    }

    /**
     * Assign the registration to the workshop session, replacing its workshop in the block of the session. A user can not
     * take part in a workshop twice, so this fails (returns false) if the registration has the workshop in another block.
     */
    public function assignWorkshopSession(WorkshopSession $session): bool
    {
        $hasWorkshopInOtherBlock = $this->workshopAssignments()
            ->where('workshop_block_id', '!=', $session->workshop_block_id)
            ->whereRelation('session', 'workshop_id', $session->workshop_id)
            ->exists();
        if ($hasWorkshopInOtherBlock) {
            return false;
        }

        $this->workshopAssignments()->updateOrCreate(
            ['workshop_block_id' => $session->workshop_block_id],
            ['workshop_session_id' => $session->id],
        );

        return true;
    }

    /**
     * Get group for the registration.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
