<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Seeder;

class FixEventsGerolsteinSeeder extends Seeder
{
    /**
     * Register all students for the "Spieleolympiade" that are not registered yet, e.g. before the teams are divided.
     */
    public function run(): void
    {
        $event = Event::where('name', 'Spieleolympiade')->firstOrFail();

        // get all students without a registration for the event
        $students = User::doesntHave('roles')
            ->whereDoesntHave('registrations', fn ($query) => $query->where('event_id', $event->id))
            ->get();

        // create registration
        foreach ($students as $student) {
            $registration = new Registration;
            $registration->user_id = $student->id;
            $registration->event_id = $event->id;
            $registration->save();
        }
    }
}
