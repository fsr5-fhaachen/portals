<?php

use App\Models\Event;
use App\Models\User;
use App\Models\Course;
use App\Models\Registration;
use App\Models\Group;
use App\Helpers\GroupCourseDivision;
use App\Models\CourseGroup;
use Illuminate\Database\Eloquent\Collection;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->event = Event::factory()->create(['consider_alcohol' => true])->first();
    $this->course = Course::factory()->count(1)->create()->first();
    $this->groups = Group::factory()->count(15)->create(['event_id' => $this->event->id]);

    foreach ($this->groups as $group) {
        $courseGroup = new CourseGroup();
        $courseGroup->group_id = $group->id;
        $courseGroup->course_id = $this->course->id;
        $courseGroup->save();
    }

    User::factory()->count(150)->create(['course_id' => $this->course->id]);
});

test('assign without non drinkers', function () {
    foreach (User::all() as $user) {
        Registration::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $user->id,
            'drinks_alcohol' => true,
            'fulfils_requirements' => true,
            'queue_position' => null
        ]);
    }

    $division = new GroupCourseDivision($this->event, new Collection([$this->course]), true);
    $division->assign();

    foreach ($this->groups as $group) {
        expect($group->registrations()->count())->toEqual(10);
    }
});

test('assign with few non drinkers', function () {
    foreach (User::all() as $index => $user) {
        if ($index < 2) {
            Registration::factory()->create([
                'event_id' => $this->event->id,
                'user_id' => $user->id,
                'drinks_alcohol' => false,
                'fulfils_requirements' => true,
                'queue_position' => null
            ]);
            continue;
        }
        Registration::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $user->id,
            'drinks_alcohol' => true,
            'fulfils_requirements' => true,
            'queue_position' => null
        ]);
    }

    $division = new GroupCourseDivision($this->event, new Collection([$this->course]), true);
    $division->assign();

    foreach ($this->groups as $group) {
        expect($group->registrations()->count())->toEqual(10);

        $nondrinkerCount = $group->registrations()
            ->where('drinks_alcohol', '=', false)
            ->count();

        // either only drinkers or 2 non drinkers
        if ($nondrinkerCount > 0) {
            expect($nondrinkerCount)->toEqual(2);
        } else {
            expect($nondrinkerCount)->toEqual(0);
        }
    }
});

test('assign with more non drinkers', function () {
    foreach (User::all() as $index => $user) {
        if ($index < 50) {
            Registration::factory()->create([
                'event_id' => $this->event->id,
                'user_id' => $user->id,
                'drinks_alcohol' => false,
                'fulfils_requirements' => true,
                'queue_position' => null
            ]);
            continue;
        }
        Registration::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $user->id,
            'drinks_alcohol' => true,
            'fulfils_requirements' => true,
            'queue_position' => null
        ]);
    }

    $division = new GroupCourseDivision($this->event, new Collection([$this->course]), true);
    $division->assign();

    foreach ($this->groups as $group) {
        expect($group->registrations()->count())->toEqual(10);

        $nondrinkerCount = $group->registrations()
            ->where('drinks_alcohol', '=', false)
            ->count();

        // either only drinkers or at least 2 non drinkers per group
        if ($nondrinkerCount > 0) {
            expect($nondrinkerCount)->toBeGreaterThanOrEqual(2);
        } else {
            expect($nondrinkerCount)->toEqual(0);
        }
    }
});
