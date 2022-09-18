<?php

namespace Database\Seeders;

use App\Models\Registration;
use App\Models\User;
use Faker\Factory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StresstestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $course_id = env('SEEDER_COURSE_ID', 1);
        $event_id = env('SEEDER_EVENT_ID', 1);
        $slot_id = env('SEEDER_SLOT_ID', null);
        $amount = env('SEEDER_AMOUNT', 1);
        $nondrinker_percentage = env('SEEDER_ND_PERCENTAGE', 50);

        $faker = Factory::create();

        User::factory()
          ->has(
            Registration::factory()
              ->state([
                'event_id' => (int)$event_id,
                'slot_id' => (int)$slot_id,
                'group_id' => null,
                'drinks_alcohol' => $faker->boolean((int)$nondrinker_percentage),
                'fulfils_requirements' => false,
                'is_present' => false,
                'queue_position' => -1
              ])
              ->count(1)
          )
          ->state(['course_id' => (int)$course_id])
          ->count((int)$amount)
          ->create();
    }
}
