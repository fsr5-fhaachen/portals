<?php

namespace App\Helpers;

use App\Models\Course;
use App\Models\Event;
use App\Models\Group;
use Illuminate\Database\Eloquent\Collection;

class GroupCourseDivision extends GroupDivision
{
    protected Course $course;

    public function __construct(Event $event, Course $course, bool $assignByAlc, int $maxGroups = 0, int $maxGroupSize = 0, int $minNonDrinkers = 3)
    {
        parent::__construct($event, $assignByAlc, $maxGroups, $maxGroupSize, $minNonDrinkers);
        $this->course = $course;
        $this->registrations = $this->registrations->toQuery()
          ->join('users', 'registrations.user_id', '=', 'users.id')
          ->where('users.course_id', '=', $course->id)
          ->get();
        $this->groups = $this->groups->where('course_id', '=', $course->id);
        if ($maxGroups) $this->groups = $this->groups->take($maxGroups);
    }

    /**
     * @inheritDoc
     */
    public function getUnassignedRegs()
    {
        return $this->registrations->filter(function ($val, $key) {
           return $val->group_id == null;
        });
    }

    // TODO getOpenGroupSpots doc
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
     * @inheritDoc
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
     * @inheritDoc
     */
    protected function assignNonDrinkers()
    {
        $nonDrinkerRegs = $this->registrations->where('drinks_alcohol', '=', false)
          ->shuffle();

        // If chunking by minNonDrinkers would give more chunks than groups, increase chunk size by 1 until it fits
        $nonDrinkersPerGroup = $this->minNonDrinkers;
        while (($nonDrinkerRegs->count() / $nonDrinkersPerGroup) > $this->groups->count()) {
            $nonDrinkersPerGroup++;
        }
        $chunks = $nonDrinkerRegs->chunk($nonDrinkersPerGroup);

        // Assign each chunk to a group
        foreach ($this->groups as $group) {
            $chunk = $chunks->pop();
            if ($chunk == null) {
                return;
            }

            foreach ($chunk as $registration) {
                $registration->group_id = $group->id;
                $registration->queue_position = null;
                $registration->save();
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function assignUntilSatisfies()
    {
        $groupMinSize = floor($this->registrations->count() / $this->groups->count());

        // Get only registrations that have yet to be assigned a group
        $unassignedRegs = $this->getUnassignedRegs()
          ->shuffle();

        // Assign registrations until lower value of groupMinSize and maxGroupSize is reached for every group
        foreach ($this->groups as $group) {
            $assignLimit = ($groupMinSize <= $this->maxGroupSize) ? $groupMinSize : $this->maxGroupSize;
            $assignAmount = $assignLimit - $group->registrations()->count();

            for ($i = 0; $i < $assignAmount; $i++) {
                $registration = $unassignedRegs->pop();
                $registration->group_id = $group->id;
                $registration->queue_position = null;
                $registration->save();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function assignLeftover()
    {
        $unassignedRegs = $this->getUnassignedRegs();
        if ($this->maxGroupSize > 0) $unassignedRegs = $unassignedRegs->sortBy('queue_position');

        // Sort groupsWithOpenSpots by how many people are assigned to it, so the one with the least people is in first place
        $groupsWithOpenSpots = $this->getGroupsWithOpenSpots()
          ->sortBy(function ($group) {
            return $group->registrations()->count();
          });
        if ($groupsWithOpenSpots->isEmpty()) return;

        $cycleAssignByAlc = $this->assignByAlc;  // Determines if this assign cycle should consider alcohol consumption at any given time

        foreach ($unassignedRegs as $registration) {

          $group = $groupsWithOpenSpots->first();

          if ($cycleAssignByAlc) {

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
          if ($this->getOpenGroupSpots($group) <= 0) return;

          // Otherwise this registration can safely be assigned to the group
          $registration->group_id = $group->id;
          $registration->queue_position = null;
          $registration->save();

          // Sort the groups to have the group with least people in front again
          $groupsWithOpenSpots->sortBy(function ($group) {
            return $group->registrations()->count();
          });
        }
    }

    /**
     * @inheritDoc
     */
    protected function assignInitial()
    {
      if ($this->assignByAlc) $this->assignNonDrinkers();
      $this->assignUntilSatisfies();
      if ($this->maxGroupSize > 0) $this->updateQueuePos($this->getUnassignedRegs());
    }

    /**
     * @inheritDoc
     */
    public function assign()
    {
      // Checks if registrations have no group_id set, which indicates that course needs initial assignment
      $courseNotAssigned = $this->registrations->every(function ($val, $key) {
          return $val->group_id == null;
        });

      if ($courseNotAssigned) $this->assignInitial();
      $this->assignLeftover();
      if ($this->maxGroupSize > 0) $this->updateQueuePos($this->getUnassignedRegs()->sortBy('queue_position'));
    }
}
