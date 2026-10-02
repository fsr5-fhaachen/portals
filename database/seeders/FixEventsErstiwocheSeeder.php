<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class FixEventsErstiwocheSeeder extends Seeder
{
    /**
     * Update the descriptions of the "Sport" and "Kultur" events.
     */
    public function run(): void
    {
        Event::where('name', 'Sport')->update(['description' => EventsErstiwocheSeeder::sportDescription()]);
        Event::where('name', 'Kultur')->update(['description' => EventsErstiwocheSeeder::kulturDescription()]);
    }
}
