<?php

namespace App\Helpers;

use App\Models\Course;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Collection;

class GroupBalancedDivision extends GroupDivision
{
    private ?Collection $courses = null;

    public function __construct(Event $event, bool $assignByAlc, int $maxGroups = 0, int $maxGroupSize = 0, int $minNonDrinkers = 3)
    {
        parent::__construct($event, $assignByAlc, $maxGroups, $maxGroupSize, $minNonDrinkers);
        if ($maxGroups) {
            $this->groups = $this->groups->take($maxGroups);
        }
    }

    /**
     * Returns all courses, loaded once and cached for the lifetime of this division.
     */
    protected function courses(): Collection
    {
        return $this->courses ??= Course::all();
    }

    // TODO calcOptFill doc
    protected function calcOptFill(Collection $registrations)
    {
        $optFill = [];

        foreach ($this->courses() as $course) {
            $registrationsOfCourse = $registrations->filter(function (Registration $reg) use ($course) {
                return $reg->user->course_id == $course->id;
            });

            if ($this->maxGroupSize > 0) {
                $courseFillPercentage = $registrationsOfCourse->count() / $this->registrations->count();
                $optFill[$course->id] = floor($this->maxGroupSize * $courseFillPercentage);
            } else {
                $optFill[$course->id] = floor($registrationsOfCourse->count() / $this->groups->count());
            }
        }

        return $optFill;
    }

    // TODO calcCurrFill doc
    protected function calcCurrFill()
    {
        $currFill = [];

        foreach ($this->groups as $group) {
            foreach ($this->courses() as $course) {
                $registrationsOfGroupAndCourse = $this->registrations->filter(function (Registration $reg) use ($course, $group) {
                    return $reg->user->course_id == $course->id && $reg->group_id == $group->id;
                });
                $currFill[$group->id][$course->id] = $registrationsOfGroupAndCourse->count();
            }
        }

        return $currFill;
    }

    // TODO calcFillRate doc
    protected function calcFillRate(Collection $registrations)
    {
        $fillRate = [];
        $optFill = $this->calcOptFill($registrations);
        $currFill = $this->calcCurrFill();

        foreach ($this->courses() as $course) {
            $optFillForCourse = $optFill[$course->id];
            foreach ($this->groups as $group) {
                $fillRate[$group->id][$course->id] = $optFillForCourse - $currFill[$group->id][$course->id];
            }
        }

        return $fillRate;
    }

    // TODO assignBalanced doc
    protected function assignBalanced(Collection $toBeAssignedRegs, Collection $totalRegs)
    {
        $fillRate = $this->calcFillRate($totalRegs);

        foreach ($this->courses() as $course) {
            $regsOfCourse = $toBeAssignedRegs->filter(function (Registration $reg) use ($course) {
                return $reg->user->course_id == $course->id;
            })
                ->shuffle();

            foreach ($this->groups as $group) {
                // Assign registrations of given course to a group until fill rate of course for that group is hit
                for ($i = 0; $i < $fillRate[$group->id][$course->id]; $i++) {
                    // Stop if no more registrations of given course are unassigned
                    if ($regsOfCourse->count() <= 0) {
                        break;
                    }

                    $registration = $regsOfCourse->pop();
                    $registration->group_id = $group->id;
                    $registration->queue_position = null;
                    $registration->save();
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function assignNonDrinkers()
    {
        $nonDrinkerRegs = $this->registrations->where('drinks_alcohol', '=', false);

        $nonDrinkerFillRates = $this->calcFillRate($nonDrinkerRegs);

        // If you can satisfy minNonDrinkers requirement with a balanced fill, do it first
        if (array_sum($nonDrinkerFillRates[min(array_keys($nonDrinkerFillRates))]) > $this->minNonDrinkers) {
            $this->assignBalanced($nonDrinkerRegs, $nonDrinkerRegs);
        }

        // Assign yet unassigned non-drinkers
        $nonDrinkerRegs = $nonDrinkerRegs->where('group_id', '=', null)
            ->shuffle();

        // If chunking by minNonDrinkers would give more chunks than groups, increase chunk size by 1 until it fits
        $nonDrinkersPerGroup = $this->minNonDrinkers;
        while (($nonDrinkerRegs->count() / $nonDrinkersPerGroup) > $this->groups->count()) {
            $nonDrinkersPerGroup++;
        }
        $chunks = $nonDrinkerRegs->chunk($nonDrinkersPerGroup);

        // Assign each chunk to a group
        foreach ($this->groups as $group) {
            if ($chunks->count() == 0) {
                return;
            }
            $chunk = $chunks->pop();

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
        // Get only registrations that have yet to be assigned a group
        $unassignedRegs = $this->getUnassignedRegs();

        $this->assignBalanced($unassignedRegs, $this->registrations);
    }

    /**
     * {@inheritDoc}
     */
    public function assign()
    {
        // Checks if registrations have no group_id set, which indicates that course needs initial assignment
        $groupsNotAssigned = $this->registrations->every(function ($reg) {
            return $reg->group_id == null;
        });

        if ($groupsNotAssigned) {
            $this->loggingEnabled ? $this->logCurrState('Pre-assignInitial') : 0;
            $this->assignInitial();
            $this->loggingEnabled ? $this->logCurrState('Post-assignInitial') : 0;
        }
        $this->loggingEnabled ? $this->logCurrState('Pre-assignLeftover') : 0;
        $this->assignLeftover();
        if ($this->maxGroupSize > 0) {
            $this->updateQueuePos($this->getUnassignedRegs()->sortBy('queue_position'));
        }
        $this->loggingEnabled ? $this->logCurrState('Post-assignLeftover') : 0;
    }
}
