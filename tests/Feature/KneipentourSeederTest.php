<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Course;
use App\Models\Event;
use App\Models\Group;
use App\Models\Module;
use Database\Seeders\KneipentourSeeder;
use Database\Seeders\TutorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KneipentourSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Skip the tutor seeds, because they depend on the local (gitignored) tutors.csv.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(TutorSeeder::class, new class extends TutorSeeder
        {
            public function run(): void {}
        });
    }

    public function test_it_seeds_only_the_kneipentour_with_eight_groups(): void
    {
        $this->seed(KneipentourSeeder::class);

        $this->assertSame(['Kneipentour'], Event::pluck('name')->all());

        $event = Event::first();
        $this->assertSame('group_phase', $event->type);
        $this->assertTrue((bool) $event->consider_alcohol);

        $groups = $event->groups()->orderBy('id')->get();
        $this->assertSame(
            ['Gruppe 1', 'Gruppe 2', 'Gruppe 3', 'Gruppe 4', 'Gruppe 5', 'Gruppe 6', 'Gruppe 7', 'Gruppe 8'],
            $groups->pluck('name')->all()
        );
        $this->assertTrue($groups->every(fn (Group $group) => $group->telegram_group_link === null));
    }

    public function test_it_seeds_only_the_faculty_courses(): void
    {
        $this->seed(KneipentourSeeder::class);

        $this->assertSame(
            ['FB1' => 'fa-map', 'FB4' => 'fa-paint-brush', 'FB5' => 'fa-plug'],
            Course::orderBy('abbreviation')->pluck('icon', 'abbreviation')->all()
        );
        $this->assertTrue(Course::all()->every(fn (Course $course) => (bool) $course->show_on_registration));
    }

    public function test_it_opens_the_registration_for_one_week_from_now(): void
    {
        $this->freezeSecond();

        $this->seed(KneipentourSeeder::class);

        $event = Event::first();
        $this->assertTrue($event->registration_from->equalTo(now()));
        $this->assertTrue($event->registration_to->equalTo(now()->addWeek()));
    }

    public function test_it_activates_only_the_registration_and_scoring_modules(): void
    {
        $this->seed(KneipentourSeeder::class);

        $this->assertEqualsCanonicalizing(
            ['registration', 'scoring'],
            Module::where('active', true)->pluck('key')->all()
        );
    }

    public function test_it_creates_a_closed_competition_with_one_team_per_group(): void
    {
        $this->seed(KneipentourSeeder::class);

        $event = Event::first();
        $competition = Competition::sole();
        $this->assertSame('Kneipentour', $competition->name);
        $this->assertSame($event->id, $competition->event_id);
        $this->assertFalse($competition->is_open);

        $teams = $competition->teams()->get();
        $this->assertCount(8, $teams);
        $this->assertEqualsCanonicalizing(
            $event->groups()->pluck('id')->all(),
            $teams->pluck('group_id')->all()
        );
    }

    public function test_it_does_not_duplicate_data_when_run_twice(): void
    {
        $this->seed(KneipentourSeeder::class);
        $this->seed(KneipentourSeeder::class);

        $this->assertSame(1, Event::count());
        $this->assertSame(8, Group::count());
        $this->assertSame(1, Competition::count());
        $this->assertSame(3, Course::count());
    }
}
