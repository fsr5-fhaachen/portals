<?php

namespace Tests\Feature;

use App\Helpers\GroupBalancedDivision;
use App\Models\Event;
use App\Models\User;
use App\Models\Course;
use App\Models\Registration;
use App\Models\Group;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function PHPUnit\Framework\assertTrue;

class GroupBalancedDivisionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * most simple case. All courses distributed equally with no non-drinkers
     */
    public function testEqualDivOnlyDrinkers(): void
    {
        $event = Event::factory()->create(['consider_alcohol' => true])->first();
        $courses = Course::factory()->count(10)->create();
        $groups = Group::factory()->count(10)->create(['course_id' => null, 'event_id' => $event->id]);

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
                $this->assertEquals(
                    2,
                    $group->registrations()
                        ->join('users', 'registrations.user_id', '=', 'users.id')
                        ->where('users.course_id', '=', $course->id)
                        ->count()
                );
            }
        }
    }


    /**
     * not all courses distributed equally with no non-drinkers
     */
    public function testUnequalDivOnlyDrinkers(): void
    {
        $event = Event::factory()->create(['consider_alcohol' => true])->first();
        $courses = Course::factory()->count(10)->create();
        $groups = Group::factory()->count(10)->create(['course_id' => null, 'event_id' => $event->id]);

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
                $this->assertEquals(
                    $i,
                    $group->registrations()
                        ->join('users', 'registrations.user_id', '=', 'users.id')
                        ->where('users.course_id', '=', $course->id)
                        ->count()
                );
                $i += 1;
            }
        }
    }

    /**
     * not all courses distributed equally with non-drinkers
     */
    public function testUnequalDivNondrinkers(): void
    {
        $event = Event::factory()->create(['consider_alcohol' => true])->first();
        $courses = Course::factory()->count(10)->create();
        $groups = Group::factory()->count(10)->create(['course_id' => null, 'event_id' => $event->id]);

        $i = 10;

        foreach ($courses as $course) {
            User::factory()->count($i)->create(['course_id' => $course->id]);
            $i += 10;
        }

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
                $nondrinkerCount = $group->registrations()
                    ->where('drinks_alcohol', '=', false)
                    ->count();

                // either only drinkers or at least 3 non drinkers
                if ($nondrinkerCount > 0) {
                    $this->assertGreaterThanOrEqual(3, $nondrinkerCount);
                } else {
                    $this->assertEquals(0, $nondrinkerCount);
                }

                // check course balance (with allowed deviation due to priority for assigning non drinkers)
                $i = 1;
                foreach ($courses as $course) {
                    $courseCount = $group->registrations()
                        ->join('users', 'registrations.user_id', '=', 'users.id')
                        ->where('users.course_id', '=', $course->id)
                        ->count();

                    $this->assertGreaterThanOrEqual($i - 2, $courseCount);
                    $this->assertLessThanOrEqual($i + 2, $courseCount);
                    $i += 1;
                }
            }

            Registration::truncate();
        }
    }
}
