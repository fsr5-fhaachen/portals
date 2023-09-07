<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Group;
use App\Models\Slot;
use DateTime;
use Illuminate\Database\Seeder;

class EventsDemoSeeder extends Seeder
{
    /**
     * Run the events seeds.
     */
    public function run(): void
    {
        $this->runGroupPhase();
        $this->runEventRegistration();
        $this->runSlotBooking();
    }

    /**
     * Run the "groupPhase" event seeds.
     */
    public function runGroupPhase(): void
    {
        // check if event with name "group_phase" exists
        $event = Event::where('name', 'group_phase')->first();
        if ($event) {
            return;
        }

        // set registration_from and registration_to
        $registration_from = new DateTime(NOW());
        $registration_to = new DateTime(NOW());
        $registration_to->modify('+7 days');

        // create a new event
        $event = new Event();
        $event->name = 'group_phase';
        $event->description = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed vitae eros vitae nisl ultrices ult.';
        $event->type = 'group_phase';
        $event->registration_from = $registration_from;
        $event->registration_to = $registration_to;
        $event->has_requirements = false;
        $event->consider_alcohol = true;
        $event->sort_order = 100;

        // save the event
        $event->save();

        // create event groups
        $groups = [];
        for ($i = 1; $i <= 14; $i++) {
            $groups[] = [
                'name' => "Gruppe $i",
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
     * Run the "event_registration" event seeds.
     */
    public function runEventRegistration(): void
    {
        // check if event with name "event_registration" exists
        $event = Event::where('name', 'event_registration')->first();
        if ($event) {
            return;
        }

        // set registration_from and registration_to
        $registration_from = new DateTime(NOW());
        $registration_to = new DateTime(NOW());
        $registration_to->modify('+7 days');

        // create a new event
        $event = new Event();
        $event->name = 'event_registration';
        $event->description = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed vitae eros vitae nisl ultrices ult.';
        $event->type = 'event_registration';
        $event->registration_from = $registration_from;
        $event->registration_to = $registration_to;
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 200;
        $event->form = '[
            {
                "$formkit": "select",
                "name": "lorem",
                "label": "Lorem",
                "options": {
                    "ipsum": "Ipsum",
                    "dolor": "Dolor",
                    "sit": "Sit"
                },
                "placeholder": "Lorem ipsum dolor sit",
                "validation": "required"
            }
        ]';

        // save the event
        $event->save();
    }

    /**
     * Run the "slot_booking" event seeds.
     */
    public function runSlotBooking(): void
    {
        // check if event with name "slot_booking" exists
        $event = Event::where('name', 'slot_booking')->first();
        if ($event) {
            return;
        }

        // set registration_from and registration_to
        $registration_from = new DateTime(NOW());
        $registration_to = new DateTime(NOW());
        $registration_to->modify('+7 days');

        // create a new event
        $event = new Event();
        $event->name = 'slot_booking';
        $event->description = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed vitae eros vitae nisl ultrices ult.';
        $event->type = 'slot_booking';
        $event->registration_from = $registration_from;
        $event->registration_to = $registration_to;
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 300;

        // save the event
        $event->save();

        // create event slots
        $slots = [
            [
                'name' => 'Lorem ipsum',
                'has_requirements' => true,
                'maximum_participants' => 10,
            ],
            [
                'name' => 'Dolor sit',
                'has_requirements' => true,
                'maximum_participants' => 20,
            ],
            [
                'name' => 'Amet consectetur',
                'has_requirements' => true,
                'maximum_participants' => 30,
            ],
            [
                'name' => 'Adipiscing elit',
                'has_requirements' => true,
                'maximum_participants' => 40,
            ],
            [
                'name' => 'Sed vitae eros',
                'has_requirements' => false,
                'maximum_participants' => null,
            ],
            [
                'name' => 'Vitae nisl',
                'has_requirements' => false,
                'maximum_participants' => null,
            ],
            [
                'name' => 'Ultrices ult',
                'has_requirements' => false,
                'maximum_participants' => null,
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