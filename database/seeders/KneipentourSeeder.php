<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\CompetitionTeam;
use App\Models\Course;
use App\Models\Event;
use App\Models\Group;
use Illuminate\Database\Seeder;

class KneipentourSeeder extends Seeder
{
    /**
     * Set the number of groups for the Kneipentour.
     *
     * @var int
     */
    private const GROUP_COUNT = 8;

    /**
     * Seed a standalone instance with only the Kneipentour event, the registration and the scoring module.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ModuleSeeder::class,
        ]);

        $this->runCourses();

        $this->call([
            TutorSeeder::class,
            ModuleErstiwocheSeeder::class,
        ]);

        $this->runKneipentour();
    }

    /**
     * Run the course seeds with one course per faculty instead of the regular study programmes.
     */
    private function runCourses(): void
    {
        $courses = [
            [
                'name' => 'FB1 Architektur',
                'abbreviation' => 'FB1',
                'icon' => 'fa-map',
                'classes' => 'bg-emerald-700 text-white dark:bg-emerald-800',
            ],
            [
                'name' => 'FB4 Gestaltung',
                'abbreviation' => 'FB4',
                'icon' => 'fa-paint-brush',
                'classes' => 'bg-violet-800 text-white dark:bg-violet-900',
            ],
            [
                'name' => 'FB5 Elektrotechnik und Informationstechnik',
                'abbreviation' => 'FB5',
                'icon' => 'fa-plug',
                'classes' => 'bg-yellow-500 text-white dark:bg-yellow-600',
            ],
        ];

        foreach ($courses as $course) {
            // check if course with abbreviation exists
            if (Course::where('abbreviation', $course['abbreviation'])->exists()) {
                continue;
            }

            // create a new course
            $newCourse = new Course;
            $newCourse->name = $course['name'];
            $newCourse->abbreviation = $course['abbreviation'];
            $newCourse->icon = $course['icon'];
            $newCourse->classes = $course['classes'];
            $newCourse->show_on_registration = true;

            // save the course
            $newCourse->save();
        }
    }

    /**
     * Run the "Kneipentour" event seeds including a scoring competition with one team per group.
     */
    private function runKneipentour(): void
    {
        // check if event with name "Kneipentour" exists
        if (Event::where('name', 'Kneipentour')->exists()) {
            return;
        }

        // create a new event
        $event = new Event;
        $event->name = 'Kneipentour';
        $event->description = '<p>Sei Teil unserer Kneipentour, um die besten Bars zu entdecken, unterhaltsame Spiele zu genießen und deine Kommilitonen kennenzulernen. Wer nicht trinkt ist trotzdem herzlich eingeladen!</p>';
        $event->type = 'group_phase';
        $event->registration_from = now();
        $event->registration_to = now()->addWeek();
        $event->has_requirements = false;
        $event->consider_alcohol = true;
        $event->sort_order = 100;

        // save the event
        $event->save();

        // create competition
        $competition = new Competition;
        $competition->name = 'Kneipentour';
        $competition->event_id = $event->id;
        $competition->is_open = false;
        $competition->save();

        // create event groups and a team for each group
        for ($i = 1; $i <= self::GROUP_COUNT; $i++) {
            $group = new Group;
            $group->name = "Gruppe $i";
            $group->event_id = $event->id;
            $group->save();

            $team = new CompetitionTeam;
            $team->competition_id = $competition->id;
            $team->group_id = $group->id;
            $team->name = $group->name;
            $team->save();
        }
    }
}
