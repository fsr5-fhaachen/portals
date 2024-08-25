<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Group;
use App\Models\Registration;
use App\Models\Slot;
use App\Models\User;
use DateTime;
use Illuminate\Database\Seeder;

class EventsGerolsteinSeeder extends Seeder
{
    /**
     * Run the events seeds.
     */
    public function run(): void
    {
        $this->runSpieleolympiade();
        $this->runSamstagabendGruppenphase();
        $this->runSamstagabendTanzenGesang();
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

    /**
     * Run the "Spieleolympiade" event seeds.
     */
    public function runSpieleolympiade(): void
    {
        // check if event with name "Spieleolympiade" exists
        $event = Event::where('name', 'Spieleolympiade')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event;
        $event->name = 'Spieleolympiade';
        $event->type = 'group_phase';
        $event->registration_from = new DateTime('2023-11-01 8:00:00');
        $event->registration_to = new DateTime('2023-11-01 8:00:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 110;

        // save the event
        $event->save();

        // create event groups
        $groupNames = [
            'Die mutigen Mojito-Mixer ',
            'Die wilden Weihnachtsbäume',
            'Die rasanten Rasenmäher',
            'Die koolen Kürbisse',
            'Die ehrgeizigen Eisbären',
            'Die pfiffigen Pfeilgiftfrösche',
            'Die musikalischen Milkakühe',
            'Die schicken Schlümpfe',
            'Die putzigen Panther',
            'Die bärenstarken Braunbären',
            'Die originellen Orchideen',
            'Die treuen Telekom-Kunden',
            'Die freshen Flamingos',
            'Die ehrenhaften Erdbeeren',
        ];
        foreach ($groupNames as $groupName) {
            $group = new Group;
            $group->name = $groupName;
            $group->event_id = $event->id;
            $group->save();
        }

        // register all students for the event
        $this->registerStudents($event);
    }

    /**
     * Run the "Gruppenphase" event seeds.
     */
    public function runSamstagabendGruppenphase(): void
    {
        // check if event with name "Gruppenphase" exists
        $event = Event::where('name', 'Gruppenphase')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event;
        $event->name = 'Gruppenphase';
        $event->type = 'group_phase';
        $event->registration_from = new DateTime('2023-11-01 8:00:00');
        $event->registration_to = new DateTime('2023-11-01 8:00:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 210;

        // save the event
        $event->save();

        // create event groups
        $groups = [];

        for ($i = 1; $i <= 7; $i++) {
            $groups[] = [
                'name' => "Gruppe $i",
            ];
        }

        // save groups
        foreach ($groups as $groupData) {
            $group = new Group;
            $group->name = $groupData['name'];
            $group->event_id = $event->id;
            $group->save();
        }

        // register all students for the event
        $this->registerStudents($event);
    }

    /**
     * Run the "Tanzen & Gesang" event seeds.
     */
    public function runSamstagabendTanzenGesang(): void
    {
        // check if event with name "Tanzen & Gesang" exists
        $event = Event::where('name', 'Tanzen & Gesang')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event;
        $event->name = 'Tanzen & Gesang';
        $event->type = 'slot_booking';
        $event->registration_from = new DateTime('2023-11-01 8:00:00');
        $event->registration_to = new DateTime('2023-11-04 10:00:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 220;

        // save the event
        $event->save();

        // create event slots
        $slots = [
            [
                'name' => 'Tanzen',
                'has_requirements' => true,
                'maximum_participants' => 22,
            ],
            [
                'name' => 'Gesang',
                'has_requirements' => true,
                'maximum_participants' => 11,
            ],
        ];

        foreach ($slots as $slotData) {
            $slot = new Slot;
            $slot->name = $slotData['name'];
            $slot->event_id = $event->id;
            $slot->has_requirements = $slotData['has_requirements'];
            $slot->maximum_participants = $slotData['maximum_participants'];

            $slot->save();
        }
    }
}
