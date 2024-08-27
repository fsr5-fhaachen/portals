<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Seeder;

class FixEventsGerolsteinSeeder extends Seeder
{
    /**
     * Run the events seeds.
     */
    public function run(): void
    {
        // check if event with name "Spieleolympiade" exists
        $event = Event::where('name', 'Spieleolympiade')->first();

        // register all students for the event
        $this->registerStudents($event);

        // check if event with name "Gruppenphase" exists
        $event = Event::where('name', 'Gruppenphase')->first();

        // register all students for the event
        $this->registerStudents($event);
    }

    /**
     * Register all students for the given event.
     */
    private function registerStudents(Event $event): void
    {
        // get all students
        $students = User::doesntHave('roles')->get();

        // create registration
        foreach ($students as $student) {
            $registration = new Registration;
            $registration->user_id = $student->id;
            $registration->event_id = $event->id;
            $registration->save();
        }
    }
}
