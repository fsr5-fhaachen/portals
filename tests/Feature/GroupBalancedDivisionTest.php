<?php

use App\Helpers\GroupBalancedDivision;
use App\Models\Event;
use App\Models\User;
use App\Models\Course;
use App\Models\Registration;
use App\Models\Group;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->event = Event::factory()->create(['consider_alcohol' => true])->first();
    $this->courses = Course::factory()->count(10)->create();
    $this->groups = Group::factory()->count(10)->create(['event_id' => $this->event->id]);
});

// most basic test for division algorithm. If this fails, then everything does
test('equal division only drinkers', function () {

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

    $div = new GroupBalancedDivision($this->event, false, 10, 20);
    $div->assign();

    foreach ($this->groups as $group) {
        // 20 registrations per group
        expect($group->registrations()->count())->toEqual(20);

        foreach ($this->courses as $course) {
            // each group should contain 2 users per course
            expect($group->registrations()
                ->join('users', 'registrations.user_id', '=', 'users.id')
                ->where('users.course_id', '=', $course->id)
                ->count())->toEqual(2);
        }
    }
});

test('random courses only drinkers', function () {
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

    $div = new GroupBalancedDivision($this->event, false, 10, 20);
    $div->assign();

    foreach ($this->groups as $group) {
        // 20 registrations per group
        expect($group->registrations()->count())->toEqual(20);
    }

    // per course: count users with this course (courseUserCount).
    // each group must have at least courseUserCount divided by group count (floored) registrations from this course, and at most one more
    // furthermore, a number of (courseUserCount modulo group count) groups must have 1 additional registration from this course
    foreach ($this->courses as $course) {
        $courseUserCount = $course->users()->count();
        $minRegsPerGroup = (int)floor($courseUserCount / 10);
        $maxRegsPerGroup = $minRegsPerGroup + 1;
        $groupsWithOneMoreExpected = $courseUserCount % 10;
        $groupsWithOneMoreActual = 0;

        foreach ($this->groups as $group) {
            $courseCount = $group->registrations()
                ->join('users', 'registrations.user_id', '=', 'users.id')
                ->where('users.course_id', '=', $course->id)
                ->count();

            expect($courseCount)->toBeGreaterThanOrEqual($minRegsPerGroup);
            expect($courseCount)->toBeLessThanOrEqual($maxRegsPerGroup);
            if ($courseCount > $minRegsPerGroup) {
                $groupsWithOneMoreActual++;
            }
        }

        expect($groupsWithOneMoreActual)->toEqual($groupsWithOneMoreExpected);
    }
});

test('random courses drinkers and nondrinkers', function () {
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

    $div = new GroupBalancedDivision($this->event, true, 10, 20, 3);
    $div->assign();

    foreach ($this->groups as $group) {
        // 20 registrations per group
        expect($group->registrations()->count())->toEqual(20);

        // check non drinker count
        $nondrinkerCount = $group->registrations()
            ->where('drinks_alcohol', '=', false)
            ->count();

        // either only drinkers or at least 3 non drinkers
        if ($nondrinkerCount > 0) {
            expect($nondrinkerCount)->toBeGreaterThanOrEqual(3);
        } else {
            expect($nondrinkerCount)->toEqual(0);
        }
    }

    // per course: count users with this course (courseUserCount).
    // each group must have at least courseUserCount divided by 10 (floored) registrations from this course, and at most one more
    // now with allowed deviation, and without modulo check, because assigning non drinkers takes priority and optimal division may not be possible
    foreach ($this->courses as $course) {
        $courseUserCount = $course->users()->count();
        $minRegsPerGroup = (int)floor($courseUserCount / 10) - 2;
        $maxRegsPerGroup = $minRegsPerGroup + 4;

        foreach ($this->groups as $group) {
            $courseCount = $group->registrations()
                ->join('users', 'registrations.user_id', '=', 'users.id')
                ->where('users.course_id', '=', $course->id)
                ->count();

            expect($courseCount)->toBeGreaterThanOrEqual($minRegsPerGroup);
            expect($courseCount)->toBeLessThanOrEqual($maxRegsPerGroup);
        }
    }
});
