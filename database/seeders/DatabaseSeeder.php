<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            CourseSeeder::class,
            TutorSeeder::class,
            //ModuleErstiwocheSeeder::class,
            //EventsErstiwocheSeeder::class,
            //PageErstiwocheSeeder::class,
            ModuleGerolsteinSeeder::class,
            EventsGerolsteinSeeder::class,
            EventsGerolsteinSeeder::class,
        ]);
    }
}
