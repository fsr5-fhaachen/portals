<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Event;
use App\Models\Group;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Seeder;

class GroupCourseDivisionTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Course::factory()
            ->count(3)
            ->create();

        Event::factory()
            ->count(2)
            ->create();

        User::factory()
            ->has(
                Registration::factory()
                    ->state([
                        'event_id' => 1,
                        'slot_id' => null,
                        'group_id' => null,
                    ])
                    ->count(1)
            )
            ->state(['course_id' => 1])
            ->count(60)
            ->create();

        User::factory()
            ->has(
                Registration::factory()
                    ->state([
                        'event_id' => 1,
                        'slot_id' => null,
                        'group_id' => null,
                    ])
                    ->count(1)
            )
            ->state(['course_id' => 2])
            ->count(20)
            ->create();

        User::factory()
            ->has(
                Registration::factory()
                    ->state([
                        'event_id' => 1,
                        'slot_id' => null,
                        'group_id' => null,
                    ])
                    ->count(1)
            )
            ->state(['course_id' => 3])
            ->count(15)
            ->create();

        User::factory()
            ->has(
                Registration::factory()
                    ->state([
                        'event_id' => 2,
                        'slot_id' => null,
                        'group_id' => null,
                    ])
                    ->count(1)
            )
            ->count(10)
            ->create();

        Group::factory()
            ->state([
                'event_id' => 1,
                'course_id' => 1,
            ])
            ->count(4)
            ->create();

        Group::factory()
            ->state([
                'event_id' => 1,
                'course_id' => 2,
            ])
            ->count(2)
            ->create();

        Group::factory()
            ->state([
                'event_id' => 1,
                'course_id' => 3,
            ])
            ->count(1)
            ->create();

        Group::factory()
            ->state(['event_id' => 2])
            ->count(6)
            ->create();
    }
}
