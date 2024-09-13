<?php

use App\Models\Event;
use App\Models\User;
use App\Models\Course;
use App\Models\Registration;
use App\Models\Group;
use App\Helpers\GroupCourseDivision;
use App\Models\CourseGroup;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->event = Event::factory()->create(['consider_alcohol' => true])->first();
    $this->courses = Course::factory()->count(10)->create();
    $this->groups = Group::factory()->count(20)->create(['event_id' => $this->event->id]);

    // two groups per course
    for ($i = 0; $i < 10; $i++) {
        $courseGroup = new CourseGroup();
        $courseGroup->group_id = $this->groups->get($i)->id;
        $courseGroup->course_id = $this->courses->get($i)->id;
        $courseGroup->save();

        $courseGroup = new CourseGroup();
        $courseGroup->group_id = $this->groups->get(19 - $i)->id;
        $courseGroup->course_id = $this->courses->get($i)->id;
        $courseGroup->save();
    }
});

test("equal users per course only drinkers", function () {
    foreach ($this->courses as $course) {
        User::factory()->count(20)->create(['course_id' => $course->id]);
    }

    foreach (User::all() as $user) {
        $reg = new Registration();
        $reg->event_id = $this->event->id;
        $reg->user_id = $user->id;
        $reg->drinks_alcohol = true;
        $reg->fulfils_requirements = true;
        $reg->queue_position = null;
        $reg->save();
    }

    $division = new GroupCourseDivision($this->event, $this->courses, false, 20, 20);
    $division->assign();

    foreach ($this->groups as $group) {
        // 10 registrations per group
        expect($group->registrations()->count())->toEqual(10);

        // get course_id for the group
        $groupCourseId = CourseGroup::where('group_id', $group->id)->first()->course_id;

        // there should only be registrations from users with this course_id
        expect($group->registrations()
            ->join('users', 'registrations.user_id', '=', 'users.id')
            ->where('users.course_id', '=', $groupCourseId)
            ->count())->toEqual(10);
    }
});

test("random users per course only drinkers", function () {
    User::factory()->count(200)->create();

    foreach (User::all() as $user) {
        $reg = new Registration();
        $reg->event_id = $this->event->id;
        $reg->user_id = $user->id;
        $reg->drinks_alcohol = true;
        $reg->fulfils_requirements = true;
        $reg->queue_position = null;
        $reg->save();
    }

    $division = new GroupCourseDivision($this->event, $this->courses, false, 20, 20);
    $division->assign();

    foreach ($this->courses as $course) {
        $courseUserCount = $course->users()->count();
        $minCourseGroupRegs = 20;
        $maxCourseGroupRegs = 20;

        if ($courseUserCount < 40) {
            // each course has 2 groups with a limit of 20 users.
            // If there are 40 or more registrations from one course, then both groups for this course need to have 20 registrations.
            // Otherwise, the users should be divided equally among the 2 groups
            $minCourseGroupRegs = (int)floor($courseUserCount / 2);
            $maxCourseGroupRegs = (int)ceil($courseUserCount / 2);
        }

        foreach ($course->groups()->get() as $courseGroup) {
            $regCount = $courseGroup->registrations()->count();
            expect($regCount)->toBeGreaterThanOrEqual($minCourseGroupRegs);
            expect($regCount)->toBeLessThanOrEqual($maxCourseGroupRegs);

            // all registrations are from this course
            expect($courseGroup->registrations()
                ->join('users', 'registrations.user_id', '=', 'users.id')
                ->where('users.course_id', '=', $course->id)
                ->count())->toEqual($regCount);
        }
    }
});

test("random users per course drinkers and non drinkers", function () {
    User::factory()->count(200)->create();

    // drinks alcohol is now random
    foreach (User::all() as $user) {
        Registration::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $user->id,
            'fulfils_requirements' => true,
            'queue_position' => null
        ]);
    }

    $division = new GroupCourseDivision($this->event, $this->courses, true, 20, 20, 3);
    $division->assign();

    foreach ($this->courses as $course) {
        $courseUserCount = $course->users()->count();
        $minCourseGroupRegs = 20;
        $maxCourseGroupRegs = 20;

        if ($courseUserCount < 40) {
            // each course has 2 groups with a limit of 20 users.
            // If there are 40 or more registrations from one course, then both groups for this course need to have 20 registrations.
            // Otherwise, the users should be divided equally among the 2 groups.
            // Tolerate small deviation because assigning non-drinkers takes priority
            $minCourseGroupRegs = (int)floor($courseUserCount / 2) - 1;
            $maxCourseGroupRegs = (int)ceil($courseUserCount / 2) + 1;
        }

        foreach ($course->groups()->get() as $courseGroup) {
            // check non drinker count
            $nondrinkerCount = $courseGroup->registrations()
                ->where('drinks_alcohol', '=', false)
                ->count();

            // either only drinkers or at least 3 non drinkers
            if ($nondrinkerCount > 0) {
                expect($nondrinkerCount)->toBeGreaterThanOrEqual(3);
            } else {
                expect($nondrinkerCount)->toEqual(0);
            }

            $regCount = $courseGroup->registrations()->count();
            expect($regCount)->toBeGreaterThanOrEqual($minCourseGroupRegs);
            expect($regCount)->toBeLessThanOrEqual($maxCourseGroupRegs);

            // all registrations are from this course
            expect($courseGroup->registrations()
                ->join('users', 'registrations.user_id', '=', 'users.id')
                ->where('users.course_id', '=', $course->id)
                ->count())->toEqual($regCount);
        }
    }
});
