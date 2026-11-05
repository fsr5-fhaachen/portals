<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseEvent;
use App\Models\CourseGroup;
use App\Models\Event;
use App\Models\Group;
use App\Models\Registration;
use App\Models\Slot;
use App\Models\User;
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
        $this->runGroupPhaseLimitedCourses();
        $this->runGroupPhaseByCourse();
        $this->runEventRegistration();
        $this->runSlotBooking();
        $this->runWorkshopBooking();
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
        $event = new Event;
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
                'telegram_group_link' => 'https://example.com/invalid-telegram-link',
            ];
        }

        // save groups
        foreach ($groups as $groupData) {
            $group = new Group;
            $group->name = $groupData['name'];
            $group->event_id = $event->id;
            $group->telegram_group_link = $groupData['telegram_group_link'];
            $group->save();
        }

        // get all users without roles
        $users = User::doesntHave('roles')->get();

        // register users to event
        foreach ($users as $user) {
            // create registration
            $event->registrations()->create([
                'user_id' => $user->id,
                'drinks_alcohol' => rand(0, 1) == 1,
            ]);
        }
    }

    /**
     * Run the "groupPhaseLimitedCourses" event seeds.
     */
    public function runGroupPhaseLimitedCourses(): void
    {
        // check if event with name "group_phase-limited_courses" exists
        $event = Event::where('name', 'group_phase-limited_courses')->first();
        if ($event) {
            return;
        }

        // set registration_from and registration_to
        $registration_from = new DateTime(NOW());
        $registration_to = new DateTime(NOW());
        $registration_to->modify('+7 days');

        // create a new event
        $event = new Event;
        $event->name = 'group_phase-limited_courses';
        $event->description = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed vitae eros vitae nisl ultrices ult.';
        $event->type = 'group_phase';
        $event->registration_from = $registration_from;
        $event->registration_to = $registration_to;
        $event->has_requirements = false;
        $event->consider_alcohol = true;
        $event->sort_order = 110;

        // save the event
        $event->save();

        // create event groups
        $groups = [];
        for ($i = 1; $i <= 14; $i++) {
            $groups[] = [
                'name' => "Gruppe $i",
                'telegram_group_link' => 'https://example.com/invalid-telegram-link',
            ];
        }

        // save groups
        foreach ($groups as $groupData) {
            $group = new Group;
            $group->name = $groupData['name'];
            $group->event_id = $event->id;
            $group->telegram_group_link = $groupData['telegram_group_link'];
            $group->save();
        }

        // get all courses
        $courses = Course::all();

        // map courses by abbreviation
        $coursesByAbbreviation = [];
        foreach ($courses as $course) {
            $coursesByAbbreviation[$course->abbreviation] = $course;
        }

        // allowed courses
        $allowedCourses = [
            'ISE-Master',
            'IS-Master',
            'INF-Master',
            'ET-Master',
        ];

        // save courses
        foreach ($allowedCourses as $courseAbbreviation) {
            $course = $coursesByAbbreviation[$courseAbbreviation];
            $course_event = new CourseEvent;
            $course_event->course_id = $course->id;
            $course_event->event_id = $event->id;
            $course_event->save();
        }

        // get all users without roles
        $users = User::doesntHave('roles')->get();

        // register users to event
        foreach ($users as $user) {
            // create registration
            $event->registrations()->create([
                'user_id' => $user->id,
                'drinks_alcohol' => rand(0, 1) == 1,
            ]);
        }
    }

    /**
     * Run the "groupPhaseByCourse" event seeds.
     */
    public function runGroupPhaseByCourse(): void
    {
        // check if event with name "group_phase_by_course" exists
        $event = Event::where('name', 'group_phase_by_course')->first();
        if ($event) {
            return;
        }

        // set registration_from and registration_to
        $registration_from = new DateTime(NOW());
        $registration_to = new DateTime(NOW());
        $registration_to->modify('+7 days');

        // create a new event
        $event = new Event;
        $event->name = 'group_phase_by_course';
        $event->description = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed vitae eros vitae nisl ultrices ult.';
        $event->type = 'group_phase';
        $event->registration_from = $registration_from;
        $event->registration_to = $registration_to;
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 150;

        // save the event
        $event->save();

        // get all courses
        $courses = Course::all();

        // map courses by abbreviation
        $coursesByAbbreviation = [];
        foreach ($courses as $course) {
            $coursesByAbbreviation[$course->abbreviation] = $course;
        }

        // create event groups
        $groups = [];

        // create groups for each course
        /*
        foreach ($coursesByAbbreviation as $abbreviation => $course) {
            for ($i = 1; $i <= rand(3, 10); $i++) {
                $groups[] = [
                    'name' => "$abbreviation-Gruppe $i",
                    'course_id' => $course->id,
                ];
            }
        }
        */

        // collect similar courses
        $coursesCollection = [
            ['ET', 'ET-Master'],
            ['INF', 'ISE-Master', 'INF-Master', 'IS-Master'],
            ['WI', 'SBE'],
            ['MCD', 'DIB'],
        ];

        // create groups for each course collection
        $index = 0;
        foreach ($coursesCollection as $collection) {
            for ($i = 1; $i <= rand(3, 10); $i++) {
                $groups[] = [
                    'name' => "$collection[0]-Gruppe $i",
                    'collection' => $index,
                ];
            }
            $index++;
        }

        // save groups
        foreach ($groups as $groupData) {
            $group = new Group;
            $group->name = $groupData['name'];
            // $group->course_id = $groupData['course_id'];
            $group->event_id = $event->id;
            $group->save();

            foreach ($coursesCollection[$groupData['collection']] as $course) {
                $coursegroup = new CourseGroup;
                $coursegroup->course_id = $coursesByAbbreviation[$course]->id;
                $coursegroup->group_id = $group->id;
                $coursegroup->save();
            }
        }

        // get all users without roles
        $users = User::doesntHave('roles')->get();

        // register users to event
        foreach ($users as $user) {
            // create registration
            $event->registrations()->create([
                'user_id' => $user->id,
            ]);
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
        $event = new Event;
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

        // get all users without roles
        $users = User::doesntHave('roles')->get();

        // register users to event
        foreach ($users as $user) {
            // create registration
            $event->registrations()->create([
                'user_id' => $user->id,
                'form_responses' => json_encode([
                    'lorem' => ['ipsum', 'dolor', 'sit'][rand(0, 2)],
                ]),
            ]);
        }
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
        $event = new Event;
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
                'telegram_group_link' => 'https://example.com/invalid-telegram-link',
            ],
            [
                'name' => 'Dolor sit',
                'has_requirements' => true,
                'maximum_participants' => 20,
                'telegram_group_link' => 'https://example.com/invalid-telegram-link',
            ],
            [
                'name' => 'Amet consectetur',
                'has_requirements' => true,
                'maximum_participants' => 30,
                'telegram_group_link' => 'https://example.com/invalid-telegram-link',
            ],
            [
                'name' => 'Adipiscing elit',
                'has_requirements' => true,
                'maximum_participants' => 40,
                'telegram_group_link' => 'https://example.com/invalid-telegram-link',
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

        // save slots
        foreach ($slots as $slotData) {
            $slot = new Slot;
            $slot->name = $slotData['name'];
            $slot->event_id = $event->id;
            $slot->has_requirements = $slotData['has_requirements'];
            $slot->maximum_participants = $slotData['maximum_participants'];
            $slot->telegram_group_link = in_array('telegram_group_link', $slotData) ? $slotData['telegram_group_link'] : null;

            $slot->save();
        }

        // get all users without roles
        $users = User::doesntHave('roles')->get();

        // get all slots of the event
        $slots = $event->slots()->get();

        // register users to event
        foreach ($users as $user) {
            // get random slot
            $slot = $slots[rand(0, count($slots) - 1)];

            // check if slot has maximum_participants
            $queuePosition = null;
            if ($slot->maximum_participants) {
                $queuePosition = Registration::where('event_id', $event->id)->where('slot_id', $slot->id)->max('queue_position');

                if (! $queuePosition || $queuePosition == -1) {
                    $queuePosition = -1;
                } else {
                    $queuePosition++;
                }
            }

            // create registration
            $event->registrations()->create([
                'user_id' => $user->id,
                'slot_id' => $slot->id,
                'queue_position' => $queuePosition ?? null,
            ]);
        }
    }

    /**
     * Run the "workshop_booking" event seeds.
     */
    public function runWorkshopBooking(): void
    {
        // check if event with name "workshop_booking" exists
        $event = Event::where('name', 'workshop_booking')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event;
        $event->name = 'workshop_booking';
        $event->description = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed vitae eros vitae nisl ultrices ult.';
        $event->type = 'workshop_booking';
        $event->registration_from = now();
        $event->registration_to = now()->addDays(7);
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 400;
        $event->workshop_priority_count = 5;

        // save the event
        $event->save();

        // create three blocks on one day
        $blocks = collect([10, 13, 15])->map(fn (int $hour, int $index) => $event->workshopBlocks()->create([
            'name' => 'Block '.($index + 1),
            'starts_at' => now()->addDays(8)->setTime($hour, 0),
            'ends_at' => now()->addDays(8)->setTime($hour + 1, 30),
        ]));

        // create workshops, some of them only take place in some blocks
        $workshops = [
            ['name' => 'Löten für Einsteiger', 'blocks' => [0, 1, 2], 'minimum' => 5, 'maximum' => 15],
            ['name' => 'Arduino', 'blocks' => [0, 1], 'minimum' => 5, 'maximum' => 20],
            ['name' => 'Laborführung', 'blocks' => [0, 1, 2], 'minimum' => 0, 'maximum' => 25],
            ['name' => 'Bewerbungstraining', 'blocks' => [1, 2], 'minimum' => 8, 'maximum' => 15],
            ['name' => 'Lerntechniken', 'blocks' => [0, 2], 'minimum' => 3, 'maximum' => null],
            ['name' => 'Robotik', 'blocks' => [2], 'minimum' => 10, 'maximum' => 12],
        ];

        foreach ($workshops as $index => $workshopData) {
            $workshop = $event->workshops()->create([
                'name' => $workshopData['name'],
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            ]);

            foreach ($workshopData['blocks'] as $blockIndex) {
                $workshop->sessions()->create([
                    'workshop_block_id' => $blocks[$blockIndex]->id,
                    'location' => 'Raum '.(101 + $index),
                    'minimum_participants' => $workshopData['minimum'],
                    'maximum_participants' => $workshopData['maximum'],
                ]);
            }
        }

        // register users without roles with random priorities
        $workshopIds = $event->workshops()->pluck('id');
        foreach (User::doesntHave('roles')->get() as $user) {
            $registration = $event->registrations()->create(['user_id' => $user->id]);

            foreach ($workshopIds->shuffle()->take($event->workshop_priority_count)->values() as $index => $workshopId) {
                $registration->workshopPriorities()->create([
                    'workshop_id' => $workshopId,
                    'priority' => $index + 1,
                ]);
            }
        }
    }
}
