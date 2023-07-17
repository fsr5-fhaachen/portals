<?php

namespace App\Http\Controllers;

use App\Helpers\GroupBalancedDivision;
use App\Helpers\GroupCourseDivision;
use App\Helpers\SlotAssignment;
use App\Models\Course;
use App\Models\Event;
use App\Models\Slot;

class DivisionController extends Controller
{
    // TODO Remove test and test2 for production
    public function test(int $eventId, int $courseId)
    {
        $course = Course::query()->find($courseId);
        $event = Event::query()->find($eventId);

        $gcdiv = new GroupCourseDivision($event, $course, true, 3, 18);
        $gcdiv->assign();

        return 'Done!';
    }

    public function test2(int $eventId)
    {
        $event = Event::query()->find($eventId);

        $gbdiv = new GroupBalancedDivision($event, true);
        $gbdiv->assign();

        return 'Done!';
    }

    public function test3(int $slotId)
    {
        $slot = Slot::query()->find($slotId);

        $sa = new SlotAssignment($slot);
        $sa->assign();

        return 'Done!';
    }
}
