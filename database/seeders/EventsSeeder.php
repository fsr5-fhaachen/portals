<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Group;
use App\Models\Slot;
use DateTime;
use Illuminate\Database\Seeder;

class EventsSeeder extends Seeder
{
    /**
     * Run the events seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->runGruppenphase();
        $this->runKaterbrunch();
        $this->runSportKultur();
    }

    /**
     * Run the "Gruppenphase" event seeds.
     *
     * @return void
     */
    public function runGruppenphase()
    {
        // check if event with name "Gruppenphase" exists
        $event = Event::where('name', 'Gruppenphase')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event();
        $event->name = 'Gruppenphase';
        $event->description = 'Während der Gruppenphase erhältst du von deinen Tutoren und Tutorinnen wichtige Informationen rund um das Studium. Außerdem ist die Gruppenphase dazu da, um direkt die anderen Erstis kennenzulernen und erste Freundschaften zu schließen.';
        $event->type = 'group_phase';
        $event->registration_from = new DateTime('2022-09-19 8:00:00');
        $event->registration_to = new DateTime('2022-09-19 12:30:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;

        // save the event
        $event->save();

        // create event groups
        $groupNames = [
            "Die waghalsigen Waschbären",
            "Die kuscheligen Koalas",
            "Die originellen Opossums",
            "Die peppigen Pinguine",
            "Die risikofreudigen Rentiere",
            "Die fluffigen Flamingos",
            "Die kreisförmigen Karpfen",
            "Die dramatischen Dackel",
            "Die oszillierten Ozelots",
            "Die zappelnden Zitterale",
            "Die schnellen Schildkröten",
            "Die schicken Spinnen",
            "Die netten Nasenbären",
            "Die berühmten Bonobos",
            "Die tapferen Tucans",
            "Die klugen Krokodile",
            "Die wundervollen Walibys",
            "Die kantigen Kaninchen",
            "Die allwissenden Aale",
            "Die erfahrenen Enten",
        ];
        foreach ($groupNames as $groupName) {
            $group = new Group();
            $group->name = $groupName;
            $group->event_id = $event->id;
            $group->save();
        }
    }

    /**
     * Run the "Katerbrunch" event seeds.
     *
     * @return void
     */
    public function runKaterbrunch()
    {
        // check if event with name "Katerbrunch" exists
        $event = Event::where('name', 'Katerbrunch')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event();
        $event->name = 'Katerbrunch';
        $event->description = '<p>Nachdem wir alle nach der Kneipentour am Mittwoch Abend etwas verkatert sind, gibt es doch nichts besseres als zusammen bei einem guten Fr&uuml;hst&uuml;ck auszukatern 😊 <br />Hierf&uuml;r bitte wir euch die 2&euro; Anmeldegeb&uuml;hr am Montag oder Mittwoch bis 12:00 Uhr im FSR zu bezahlen, sonst k&ouml;nnt ihr leider nicht teilnehmen. </p>
        <p><strong>Wann:</strong> 22.09 ab 12:30 Uhr <br /><strong>Wo:</strong> FH, am D Geb&auml;ude <br /><strong>Was mitbringen:</strong> Tasse/ Becher und Teller ggf, Picknickdecke bei gutem Wetter.</p>
        <p>Im Anschluss k&ouml;nnen wir noch gemeinsam in den Park gehen und den Tag bei ein paar runden Flunkyball ausklingen lassen 😊</p>
        <p>Wir freuen uns auf euch</p>';
        $event->type = 'event_registration';
        $event->registration_from = new DateTime('2022-09-19 8:00:00');
        $event->registration_to = new DateTime('2022-09-21 12:00:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;

        // save the event
        $event->save();
    }

    /**
     * Run the "Sport & Kultur" event seeds.
     *
     * @return void
     */
    public function runSportKultur()
    {
        // check if event with name "Sport & Kultur" exists
        $event = Event::where('name', 'Sport & Kultur')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event();
        $event->name = 'Sport & Kultur';
        $event->description = '';
        $event->type = 'slot_booking';
        $event->registration_from = new DateTime('2022-09-19 13:00:00');
        $event->registration_to = new DateTime('2022-09-20 23:59:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;

        // save the event
        $event->save();

        // create event slots
        $slots = [
            [
                'name' => 'Fußball, Volleyball, Basketbal',
                'has_requirements' => true,
                'maximum_participants' => 50.
            ],
            [
                'name' => 'Yoga',
                'has_requirements' => true,
                'maximum_participants' => 30,
            ],
            [
                'name' => 'Bouldern',
                'has_requirements' => true,
                'maximum_participants' => 30,
            ],
            [
                'name' => 'Lasertag',
                'has_requirements' => true,
                'maximum_participants' => 60,
            ],
            [
                'name' => 'Kebabtour',
                'has_requirements' => false,
                'maximum_participants' => null,
            ],
            [
                'name' => 'Stadtführung',
                'has_requirements' => true,
                'maximum_participants' => null,
            ],
            [
                'name' => 'Zoo',
                'has_requirements' => false,
                'maximum_participants' => null,
            ]
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
