<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Event;
use App\Models\Group;
use App\Models\Slot;
use DateTime;
use Illuminate\Database\Seeder;

class EventsGerolsteinSeeder extends Seeder
{
    /**
     * Run the events seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->runSpieleolympiade();
        $this->runSamstagabendGruppenphase();
        $this->runSamstagabendTanzenSketchGesang();
    }

    /**
     * Run the "Spieleolympiade" event seeds.
     *
     * @return void
     */
    public function runSpieleolympiade()
    {
        // check if event with name "Spieleolympiade" exists
        $event = Event::where('name', 'Spieleolympiade')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event();
        $event->name = 'Spieleolympiade';
        $event->type = 'group_phase';
        $event->registration_from = new DateTime('2022-10-27 8:00:00');
        $event->registration_to = new DateTime('2022-10-27 8:00:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 110;

        // save the event
        $event->save();

        // create event groups
        $groups = [];

        for ($i = 1; $i <= 14; $i++) {
            $groups[] = [
                "name" => "Gruppe $i",
            ];
        }

        // save groups
        foreach ($groups as $groupData) {
            $group = new Group();
            $group->name = $groupData['name'];
            $group->event_id = $event->id;
            $group->save();
        }
    }

    /**
     * Run the "Gruppenphase" event seeds.
     *
     * @return void
     */
    public function runSamstagabendGruppenphase()
    {
        // check if event with name "Gruppenphase" exists
        $event = Event::where('name', 'Gruppenphase')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event();
        $event->name = 'Gruppenphase';
        $event->type = 'group_phase';
        $event->registration_from = new DateTime('2022-10-27 8:00:00');
        $event->registration_to = new DateTime('2022-10-27 8:00:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 210;

        // save the event
        $event->save();

        // create event groups
        $groups = [];

        for ($i = 1; $i <= 6; $i++) {
            $groups[] = [
                "name" => "Gruppe $i",
            ];
        }

        // save groups
        foreach ($groups as $groupData) {
            $group = new Group();
            $group->name = $groupData['name'];
            $group->event_id = $event->id;
            $group->save();
        }
    }

    /**
     * Run the "Tanzen, Sketch & Gesang" event seeds.
     *
     * @return void
     */
    public function runSamstagabendTanzenSketchGesang()
    {
        // check if event with name "Tanzen, Sketch & Gesang" exists
        $event = Event::where('name', 'Tanzen, Sketch & Gesang')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event();
        $event->name = 'Tanzen, Sketch & Gesang';
        $event->type = 'slot_booking';
        $event->registration_from = new DateTime('2022-08-28 8:00:00');
        $event->registration_to = new DateTime('2022-10-29 10:00:00');
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
                'name' => 'Sketch',
                'has_requirements' => true,
                'maximum_participants' => 11,
            ],
            [
                'name' => 'Gesang',
                'has_requirements' => true,
                'maximum_participants' => 11,
            ],
        ];

        foreach ($slots as $slotData) {
            $slot = new Slot();
            $slot->name = $slotData['name'];
            $slot->event_id = $event->id;
            $slot->has_requirements = $slotData['has_requirements'];
            $slot->maximum_participants = $slotData['maximum_participants'];

            $slot->save();
        }
    }
}
