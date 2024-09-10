<?php

use App\Helpers\GroupBalancedDivision;
use App\Models\Event;
use App\Models\User;
use App\Models\Course;
use App\Models\Registration;
use App\Models\Group;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('equal div only drinkers', function () {
    $event = Event::factory()->create(['consider_alcohol' => true])->first();
    $courses = Course::factory()->count(10)->create();
    $groups = Group::factory()->count(10)->create(['event_id' => $event->id]);

    foreach ($courses as $course) {
        User::factory()->count(20)->create(['course_id' => $course->id]);
    }

    foreach (User::all() as $user) {
        Registration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'drinks_alcohol' => true,
            'fulfils_requirements' => true,
            'queue_position' => null
        ]);
    }

    $div = new GroupBalancedDivision($event, true);
    $div->assign();

    // each group should contain 2 users per course
    foreach ($groups as $group) {
        foreach ($courses as $course) {
            expect($group->registrations()
                ->join('users', 'registrations.user_id', '=', 'users.id')
                ->where('users.course_id', '=', $course->id)
                ->count())->toEqual(2);
        }
    }
});

test('unequal div only drinkers', function () {
    $event = Event::factory()->create(['consider_alcohol' => true])->first();
    $courses = Course::factory()->count(10)->create();
    $groups = Group::factory()->count(10)->create(['event_id' => $event->id]);

    $i = 10;

    foreach ($courses as $course) {
        User::factory()->count($i)->create(['course_id' => $course->id]);
        $i += 10;
    }

    foreach (User::all() as $user) {
        Registration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'drinks_alcohol' => true,
            'fulfils_requirements' => true,
            'queue_position' => null
        ]);
    }

    $div = new GroupBalancedDivision($event, true);
    $div->assign();

    foreach ($groups as $group) {
        $i = 1;
        foreach ($courses as $course) {
            expect($group->registrations()
                ->join('users', 'registrations.user_id', '=', 'users.id')
                ->where('users.course_id', '=', $course->id)
                ->count())->toEqual($i);
            $i += 1;
        }
    }
});

test('unequal div nondrinkers', function () {
    $event = Event::factory()->create(['consider_alcohol' => true])->first();
    $courses = Course::factory()->count(10)->create();
    $groups = Group::factory()->count(10)->create(['event_id' => $event->id]);

    $i = 10;

    foreach ($courses as $course) {
        User::factory()->count($i)->create(['course_id' => $course->id]);
        $i += 10;
    }

    $checks = 0;
    $balance = 0.0;

    for ($n = 0; $n < 10; $n++) {
        // drinks_alocohol not set, so it becomes random
        foreach (User::all() as $user) {
            Registration::factory()->create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'fulfils_requirements' => true,
                'queue_position' => null
            ]);
        }

        $div = new GroupBalancedDivision($event, true, 0, 0, 3);
        $div->assign();

        foreach ($groups as $group) {
            // check non drinker count
            $nondrinker_count = $group->registrations()
                ->where('drinks_alcohol', '=', false)
                ->count();

            // either only drinkers or at least 3 non drinkers
            if ($nondrinker_count > 0) {
                expect($nondrinker_count)->toBeGreaterThanOrEqual(3);
            } else {
                expect($nondrinker_count)->toEqual(0);
            }

            $i = 1;
            foreach ($courses as $course) {
                $course_count = $group->registrations()
                    ->join('users', 'registrations.user_id', '=', 'users.id')
                    ->where('users.course_id', '=', $course->id)
                    ->count();

                if ($course_count > $i) {
                    $balance += $course_count - $i;
                } else if ($course_count < $i) {
                    $balance += $i - $course_count;
                }

                $i++;
                $checks++;
            }
        }

        Registration::truncate();
    }

    // check average course balance (with allowed deviation due to priority for assigning non drinkers)
    expect($balance / $checks)->toBeLessThanOrEqual(0.5);
});
