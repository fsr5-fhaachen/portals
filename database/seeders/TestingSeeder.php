<?php

namespace Database\Seeders;

use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $event_id = config('database.seeder.event_id', 1);
        $course_id = config('database.seeder.course_id', 1);
        $slot_id = config('database.seeder.slot_id', null);
        $regs_total = config('database.seeder.regs_total', 1);
        $regs_nd = config('database.seeder.regs_nd', 1);

        // Drinkers
        $amount = (int) $regs_total - (int) $regs_nd;
        User::factory()
            ->has(
                Registration::factory()
                    ->state([
                        'event_id' => (int) $event_id,
                        'slot_id' => $slot_id,
                        'group_id' => null,
                        'drinks_alcohol' => true,
                        'fulfils_requirements' => false,
                        'is_present' => false,
                        'queue_position' => -1,
                    ])
                    ->count(1)
            )
            ->state(['course_id' => (int) $course_id])
            ->count($amount)
            ->create();

        // Non-Drinkers
        $amount = (int) $regs_nd;
        User::factory()
            ->has(
                Registration::factory()
                    ->state([
                        'event_id' => (int) $event_id,
                        'slot_id' => $slot_id,
                        'group_id' => null,
                        'drinks_alcohol' => false,
                        'fulfils_requirements' => false,
                        'is_present' => false,
                        'queue_position' => -1,
                    ])
                    ->count(1)
            )
            ->state(['course_id' => (int) $course_id])
            ->count($amount)
            ->create();
    }
}
