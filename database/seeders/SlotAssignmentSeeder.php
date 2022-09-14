<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Event;
use App\Models\Group;
use App\Models\Registration;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SlotAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      Course::factory()
        ->count(1)
        ->create();

      Event::factory()
        ->has(
          Slot::factory()
            ->state([
              'maximum_participants' => 20
            ])
            ->count(2)
        )
        ->count(2)
        ->create();

      User::factory()
        ->has(
          Registration::factory()
            ->state([
              'event_id' => 1,
              'slot_id' => 1,
              'group_id' => null
            ])
            ->count(1)
        )
        ->state(['course_id' => 1])
        ->count(30)
        ->create();

      User::factory()
        ->has(
          Registration::factory()
            ->state([
              'event_id' => 1,
              'slot_id' => 2,
              'group_id' => null
            ])
            ->count(1)
        )
        ->count(10)
        ->create();
    }
}
