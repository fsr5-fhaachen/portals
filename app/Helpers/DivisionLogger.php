<?php

namespace App\Helpers;

use App\Models\Course;
use App\Models\Event;
use App\Models\Group;

class DivisionLogger
{
    public string $logFilePath;
    public int $writeFlags;

    public function __construct($logFilePath, $writeFlags = FILE_APPEND)
    {
        $this->logFilePath = $logFilePath;
        $this->writeFlags = $writeFlags;
    }

    /**
     * Logs current state of specified event
     *
     * @param Event $event The event that will be logged
     * @return void
     */
    public function logEvent(Event $event)
    {
        $groupsTotal = $event->groups()->count();
        $regsTotal = $event->registrations()->count();
        $nonDrinkerRegsTotal = $event->registrations()->where('drinks_alcohol', '=', false)->count();

        $line = sprintf('Event %d :: %d groups, %d total regs, %d non-drinker regs (%d %%)', $event->id, $groupsTotal, $regsTotal, $nonDrinkerRegsTotal, $nonDrinkerRegsTotal/$regsTotal * 100);

        $line .= "\n";
        foreach (Course::all() as $course) {
            $regs = $event->registrations()->get();
            $regsOfCourse = $regs->toQuery()
                ->join('users', 'registrations.user_id', '=', 'users.id')
                ->where('users.course_id', '=', $course->id)
                ->get();
            $regsCount = $regsOfCourse->count();
            $nonDrinkerRegsCount = $regsOfCourse->where('drinks_alcohol', '=', false)->count();

            $line .= sprintf('%s : [ %d t (%d %%), %d nd ] ;', $course->abbreviation, $regsCount, $regsCount/$regsTotal * 100, $nonDrinkerRegsCount);
        }

        $this->logMsg($line . "\n-----\n");

        foreach ($event->groups as $group) {
            $this->logGroup($group);
        }

        $this->logMsg("-----\n");
    }

    /**
     * Logs current state of specified group
     *
     * @param Group $group The group that will be logged
     * @return void
     */
    public function logGroup(Group $group)
    {
        $regsTotal = $group->registrations()->count();
        $nonDrinkerRegsTotal = $group->registrations()->where('drinks_alcohol', '=', false)->count();

        $line = sprintf('Group %d :: %d total, %d non-drinker -- ', $group->id, $regsTotal, $nonDrinkerRegsTotal);

        if ($regsTotal == 0) {
            $line .= "\n";
            $this->logMsg($line);
            return;
        }

        $this->logMsg($line);

        $line = "";
        foreach (Course::all() as $course) {
            $regs = $group->registrations()->get();
            $regsOfCourse = $regs->toQuery()
                ->join('users', 'registrations.user_id', '=', 'users.id')
                ->where('users.course_id', '=', $course->id)
                ->get();
            $regsCount = $regsOfCourse->count();
            $nonDrinkerRegsCount = $regsOfCourse->where('drinks_alcohol', '=', false)->count();

            $line .= sprintf('%s : [ %d t (%d %%), %d nd ] ;', $course->abbreviation, $regsCount, $regsCount/$regsTotal * 100, $nonDrinkerRegsCount);
        }
        $this->logMsg($line . "\n");
    }

    /**
     * Logs a single message
     *
     * @param string $msg
     * @return void
     */
    public function logMsg(string $msg)
    {
        file_put_contents($this->logFilePath, $msg, $this->writeFlags);
    }
}
