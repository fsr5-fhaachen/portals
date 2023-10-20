<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeders.
     */
    public function run(): void
    {
        $seeders = [
            RoleSeeder::class,
            ModuleSeeder::class,
            CourseSeeder::class,
            TutorSeeder::class,
        ];

        // add seder by event
        if (config('app.event_type') == 'demo') {
            $seeders = [
                ...$seeders,
                UserDemoSeeder::class,
                ModuleDemoSeeder::class,
                EventsDemoSeeder::class,
                PageDemoSeeder::class,
            ];
        } elseif (config('app.event_type') == 'erstiwoche') {
            $seeders = [
                ...$seeders,
                ModuleErstiwocheSeeder::class,
                EventsErstiwocheSeeder::class,
                PageErstiwocheSeeder::class,
            ];
        } elseif (config('app.event_type') == 'gerolstein') {
            $seeders = [
                ...$seeders,
                ModuleGerolsteinSeeder::class,
                EventsGerolsteinSeeder::class,
                PageGerolsteinSeeder::class,
            ];
        }

        // call seeders
        $this->call($seeders);
    }
}
