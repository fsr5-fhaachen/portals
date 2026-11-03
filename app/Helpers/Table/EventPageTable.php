<?php

namespace App\Helpers\Table;

use App\Models\Event;
use Illuminate\Http\Request;

/**
 * Selects the table of an event page depending on the event type.
 */
class EventPageTable
{
    /**
     * Get the table and the name of its prop for the event page.
     *
     * @return array{0: string, 1: Table}|null
     */
    public static function for(Request $request, Event $event): ?array
    {
        return match ($event->type) {
            'group_phase' => ['groups', new GroupsTable($request, $event->id)],
            'slot_booking' => ['slots', new SlotsTable($request, $event->id)],
            'event_registration' => ['registrations', new RegistrationsTable($request, eventId: $event->id)],
            default => null,
        };
    }
}
