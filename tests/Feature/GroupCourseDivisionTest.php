<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Event;
use App\Models\User;
use App\Models\Course;
use App\Models\Registration;
use App\Models\Group;
use App\Helpers\GroupCourseDivision;

class GroupCourseDivisionTest extends TestCase
{
    use RefreshDatabase;

    private $event;
    private $course;
    private $groups;

    public function setUp(): void
    {
        parent::setUp();

        $this->event = Event::factory()->create(['consider_alcohol' => true])->first();
        $this->course = Course::factory()->count(1)->create()->first();
        $this->groups = Group::factory()->count(15)->create(['course_id' => $this->course->id, 'event_id' => $this->event->id]);

        User::factory()->count(150)->create(['course_id' => $this->course->id]);
    }

    public function testAssignWithoutNonDrinkers()
    {
        foreach (User::all() as $user) {
            Registration::factory()->create([
                'event_id' => $this->event->id,
                'user_id' => $user->id,
                'drinks_alcohol' => true,
                'fulfils_requirements' => true,
                'queue_position' => null
            ]);
        }

        $division = new GroupCourseDivision($this->event, $this->course, true);
        $division->assign();

        foreach ($this->groups as $group) {
            $this->assertEquals(10, $group->registrations()->count());
        }
    }

    public function testAssignWithFewNonDrinkers()
    {
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

        $division = new GroupCourseDivision($this->event, $this->course, true);
        $division->assign();

        foreach ($this->groups as $group) {
            $this->assertEquals(10, $group->registrations()->count());

            $nondrinkerCount = $group->registrations()
                ->where('drinks_alcohol', '=', false)
                ->count();

            // either only drinkers or 2 non drinkers
            if ($nondrinkerCount > 0) {
                $this->assertEquals(2, $nondrinkerCount);
            } else {
                $this->assertEquals(0, $nondrinkerCount);
            }
        }
    }

    public function testAssignWithMoreNonDrinkers()
    {
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

        $division = new GroupCourseDivision($this->event, $this->course, true);
        $division->assign();

        foreach ($this->groups as $group) {
            $this->assertEquals(10, $group->registrations()->count());

            $nondrinkerCount = $group->registrations()
                ->where('drinks_alcohol', '=', false)
                ->count();

            // either only drinkers or at least 2 non drinkers per group
            if ($nondrinkerCount > 0) {
                $this->assertGreaterThanOrEqual(2, $nondrinkerCount);
            } else {
                $this->assertEquals(0, $nondrinkerCount);
            }
        }
    }
}
