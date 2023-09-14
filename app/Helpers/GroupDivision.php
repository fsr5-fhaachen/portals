<?php

namespace App\Helpers;

use App\Models\Event;
use App\Models\Group;
use Illuminate\Database\Eloquent\Collection;

abstract class GroupDivision
{
    protected Event $event;

    protected Collection $groups;

    protected Collection $registrations;

    protected bool $assignByAlc;

    protected int $maxGroups;

    protected int $maxGroupSize;

    protected int $minNonDrinkers;

    protected bool $loggingEnabled;

    protected string $loggingFilePath;

    public function __construct(Event $event, bool $assignByAlc, int $maxGroups = 0, int $maxGroupSize = 0, int $minNonDrinkers = 3)
    {
        $this->event = $event;
        $this->groups = $event->groups()->get();
        $this->registrations = $event->registrations()->get();
        $this->assignByAlc = $assignByAlc;
        $this->maxGroups = $maxGroups;
        $this->maxGroupSize = $maxGroupSize;
        $this->minNonDrinkers = $minNonDrinkers;

        $this->loggingEnabled = false;
        $this->loggingFilePath = storage_path('logs/'.env('DIVISION_DEBUG_LOG', 'division.log'));
    }

    /**
     * Enables detailed logging for this division to a specified file path. Use before calling "assign()"
     *
     * @param  string  $logFilePath Path where the logs will be written
     * @return void
     */
    public function enableLogging(string $logFilePath = '')
    {
        $this->loggingEnabled = true;
        if ($logFilePath) {
            $this->loggingFilePath = $logFilePath;
        }
    }

    /**
     * Logs current state of this division (meaning groups with info on their assigned registrations) if logging is enabled
     *
     * @param  string  $statename Describes what state the division is in at the time of logging
     * @return void
     */
    public function logCurrState(string $statename)
    {
        if ($this->loggingEnabled == false) {
            return;
        }

        $logger = new DivisionLogger($this->loggingFilePath);

        $logger->logMsg("-----\n DIVISION STATE: ".$statename."\n-----\n");
        $logger->logEvent($this->event);
    }

    /**
     * Returns all registrations for this event that have yet to be assigned a group
     *
     * @return Collection
     */
    public function getUnassignedRegs()
    {
        return $this->registrations->where('group_id', '=', null);
    }

    /**
     * Returns amount of open slots of provided group
     *
     *
     * @return int
     */
    public function getOpenGroupSpots(Group $group)
    {
        $takenSpots = $group->registrations()->count();

        return $this->maxGroupSize ? ($this->maxGroupSize - $takenSpots) : PHP_INT_MAX;
    }

    /**
     * Returns collection of groups that still have spots open
     *
     * @return Collection
     */
    public function getGroupsWithOpenSpots()
    {
        $groupsWithOpenSpots = $this->groups;

        // If there is a maxGroupSize, only assign to groups that have not yet hit it
        if ($this->maxGroupSize > 0) {
            $groupsWithOpenSpots = $groupsWithOpenSpots->filter(function ($val, $key) {
                return $val->registrations()->count() < $this->maxGroupSize;
            });
        }

        return $groupsWithOpenSpots;
    }

    /**
     * Updates the queue positions of the provided registrations
     *
     *
     * @return void
     */
    protected function updateQueuePos(Collection $registrations)
    {
        $i = 1;
        foreach ($registrations as $registration) {
            $registration->queue_position = $i;
            $registration->save();
            $i++;
        }
    }

    /**
     * First initial assignment of users that are registered for this event
     *
     * @return void
     */
    protected function assignInitial()
    {
        if ($this->assignByAlc) {
            $this->assignNonDrinkers();
            $this->loggingEnabled ? $this->logCurrState('Post-assignNonDrinkers') : 0;
        }
        $this->assignUntilSatisfies();
        $this->loggingEnabled ? $this->logCurrState('Post-assignUntilSatisfies') : 0;
        if ($this->maxGroupSize > 0) {
            $this->updateQueuePos($this->getUnassignedRegs());
        }
    }

    /**
     * Assigns non-drinkers registered for this event to groups
     *
     * @return void
     */
    abstract protected function assignNonDrinkers();

    /**
     * Assigns users registered for this event to groups in a way that satisfies requirements to that specific division algorithm
     *
     * @return void
     */
    abstract protected function assignUntilSatisfies();

    /**
     * Assigns users registered for this event that have yet to be assigned a group to groups
     *
     * @return void
     */
    public function assignLeftover()
    {
        $unassignedRegs = $this->getUnassignedRegs();
        if ($this->maxGroupSize > 0) {
            $unassignedRegs = $unassignedRegs->sortBy('queue_position');
        }

        // Sort groupsWithOpenSpots by how many people are assigned to it, so the one with the least people is in first place
        $groupsWithOpenSpots = $this->getGroupsWithOpenSpots()
            ->sortBy(function ($group) {
                return $group->registrations()->count();
            });
        if ($groupsWithOpenSpots->isEmpty()) {
            return;
        }

        $cycleAssignByAlc = $this->assignByAlc;  // Determines if this assign cycle should consider alcohol consumption at any given time

        while ($unassignedRegs->isNotEmpty()) {
            $group = $groupsWithOpenSpots->first();

            // If there are no non-drinkers left, the following assign cycles can safely skip alcohol considerations
            if ($cycleAssignByAlc && $unassignedRegs->where('drinks_alcohol', '=', false)->count() == 0) {
                $cycleAssignByAlc = false;
            }

            $registration = $unassignedRegs->pop();

            if ($cycleAssignByAlc && $registration->drinks_alcohol) {
                $group = $groupsWithOpenSpots->filter(function ($val, $key) {
                    return $val->registrations()
                        ->where('drinks_alcohol', '=', false)
                        ->count() > 0;
                })
                    ->first();

                // If there is no more group with non-drinkers that has open spots left, remove all non-drinkers from this assign cycle and continue without doing this check anymore
                if ($group == null) {
                    $unassignedRegs = $unassignedRegs->where('drinks_alcohol', '=', true);
                    $cycleAssignByAlc = false;

                    continue;
                }
            }

            // If the first group has no more spots left it implies the same for all other groups, so this assignment cycle is finished
            if ($this->getOpenGroupSpots($group) <= 0) {
                return;
            }

            // Otherwise this registration can safely be assigned to the group
            $registration->group_id = $group->id;
            $registration->queue_position = null;
            $registration->save();

            // Sort the groups to have the group with least people in front again
            $groupsWithOpenSpots = $groupsWithOpenSpots->sortBy(function ($group) {
                return $group->registrations()->count();
            });
        }
    }

    /**
     * Assigns users registered for this event to groups
     *
     * @return void
     */
    abstract public function assign();
}
