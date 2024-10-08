<?php

namespace App\Helpers;

use App\Models\Course;
use App\Models\Event;
use Illuminate\Database\Eloquent\Collection;

class GroupCourseDivision extends GroupDivision
{
    protected Collection $courses;

    public function __construct(Event $event, Collection $courses, bool $assignByAlc, int $maxGroups = 0, int $maxGroupSize = 0, int $minNonDrinkers = 3)
    {
        parent::__construct($event, $assignByAlc, $maxGroups, $maxGroupSize, $minNonDrinkers);
        $this->courses = $courses;

        //$this->registrations = $event->registrations()->with('user')->whereRelation('user', 'course_id', '=', $course->id)->get();
        $courseIds = $courses->pluck('id')->toArray();
        $this->registrations = $event->registrations()
            ->with('user')
            ->whereHas('user', function ($query) use ($courseIds) {
                $query->whereIn('course_id', $courseIds);
            })
            ->get();

        //$this->groups = $this->groups->where('course_id', '=', $course->id);
        $this->groups = $this->event->groups()
            ->whereHas('courses', function ($query) use ($courseIds) {
            $query->whereIn('courses.id', $courseIds);
        })
        ->get();

        if ($maxGroups) {
            $this->groups = $this->groups->take($maxGroups);
        }
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    protected function assignUntilSatisfies()
    {
        $groupMinSize = floor($this->registrations->count() / $this->groups->count());
        if ($groupMinSize < 1) {
            return;
        }

        // Get only registrations that have yet to be assigned a group
        $unassignedRegs = $this->getUnassignedRegs()
            ->shuffle();

        if ($this->maxGroupSize > 0) {
            $assignLimit = ($groupMinSize < $this->maxGroupSize) ? $groupMinSize : $this->maxGroupSize;
        } else {
            $assignLimit = $groupMinSize;
        }

        // Assign registrations until lower value of groupMinSize and maxGroupSize is reached for every group
        foreach ($this->groups as $group) {
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
     * {@inheritDoc}
     */
    public function assign()
    {
        if ($this->groups->count() <= 0) {
            return;
        }

        // Checks if registrations have no group_id set, which indicates that course needs initial assignment
        $courseNotAssigned = $this->registrations->every(function ($val, $key) {
            return $val->group_id == null;
        });

        if ($courseNotAssigned) {
            $this->loggingEnabled ? $this->logCurrState('Pre-assignInitial') : 0;
            $this->assignInitial();
            $this->loggingEnabled ? $this->logCurrState('Post-assignInitial') : 0;
        }
        $this->loggingEnabled ? $this->logCurrState('Pre-assignLeftover') : 0;
        $this->assignLeftover();
        if ($this->maxGroupSize > 0) {
            $this->updateQueuePos($this->getUnassignedRegs()->sortBy('queue_position'));
        }
        $this->loggingEnabled ? $this->logCurrState('Pre-assignLeftover') : 0;
    }
}
