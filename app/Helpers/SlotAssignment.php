<?php

namespace App\Helpers;

use App\Models\Slot;
use Illuminate\Database\Eloquent\Collection;

class SlotAssignment
{
    private Slot $slot;
    private int $maxParticipants;

    public function __construct(Slot $slot)
    {
        $this->slot = $slot;
        $this->maxParticipants = $slot->maximum_participants ? $slot->maximum_participants : PHP_INT_MAX; // If maxParticipants is null assign without limit
    }

    /**
     * Assigns registrations of provided collection up to the specified amount
     *
     * @param Collection $registrations
     * @param int $amount
     *
     * @return void
     */
    private function assignAmount(Collection &$registrations, int $amount)
    {
        for ($i = 0; $i < $amount; $i++) {
            $registration = $registrations->pop();
            if ($registration == null) {
                return;
            }

            $registration->queue_position = null;
            $registration->save();
        }
    }

    /**
     * Updates the queue positions of the provided registrations
     *
     * @param Collection $registrations
     *
     * @return void
     */
    private function updateQueuePos(Collection $registrations)
    {
        $i = 1;
        foreach ($registrations as $registration) {
            $registration->queue_position = $i;
            $registration->save();
            $i++;
        }
    }

    /**
     * First initial assignment of users that are registered for this slot. Operates on first-come-first-serve basis
     *
     * @return void
     */
    private function assignInitial()
    {
        $openRegistrations = $this->slot->registrations()->get()->reverse();

        // Assign until participant limit is hit
        $this->assignAmount($openRegistrations, $this->maxParticipants);

        $openRegistrations = $openRegistrations->reverse();

        // Assign remaining registrations ascending queue positions
        $this->updateQueuePos($openRegistrations);
    }

    /**
     * Assigns users registered for this slot that are in the queue
     *
     * @return void
     */
    private function assignQueue()
    {
        $currParticipants = $this->slot->registrations()->get()
          ->where('queue_position', '=', null)
          ->count();

        if ($currParticipants >= $this->maxParticipants) {
            return;
        }

        $openSpots = $this->maxParticipants - $currParticipants;
        $openRegistrations = $this->slot->registrations()->get()
          ->where('queue_position', '>', 0)
          ->sortByDesc('queue_position');

        // Assign remaining spots to registrations with lowest queue position
        $this->assignAmount($openRegistrations, $openSpots);

        $openRegistrations = $openRegistrations->sortBy('queue_position');

        // Update queue positions for the remaining registrations
        $this->updateQueuePos($openRegistrations);
    }

    /**
     * Assigns users registered for this slot
     *
     * @return void
     */
    public function assign()
    {
        // Checks if registrations have no queue_position higher than 0, which indicates that slot needs initial assignment
        $slotNotAssigned = $this->slot->registrations()->get()
          ->every(function ($val, $key) {
              return $val->queue_position <= 0;
          });

        if ($slotNotAssigned) {
            $this->assignInitial();
        }

        $this->assignQueue();
    }
}
