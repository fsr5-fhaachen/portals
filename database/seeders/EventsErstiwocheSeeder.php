<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Event;
use App\Models\Group;
use App\Models\Slot;
use DateTime;
use Illuminate\Database\Seeder;

class EventsErstiwocheSeeder extends Seeder
{
    /**
     * Run the events seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->runGruppenphase();
        $this->runStadtrallye();
        $this->runHausfuehrungOffline();
        $this->runHausfuehrungOnline();
        $this->runKneipentour();
        $this->runKaterbrunch();
        $this->runKultur();
        $this->runSport();
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
        $event->sort_order = 100;

        // save the event
        $event->save();

        // create event groups
        $groupNames = [
            'Die waghalsigen Waschbären',
            'Die kuscheligen Koalas',
            'Die originellen Opossums',
            'Die peppigen Pinguine',
            'Die risikofreudigen Rentiere',
            'Die fluffigen Flamingos',
            'Die kreisförmigen Karpfen',
            'Die dramatischen Dackel',
            'Die oszillierten Ozelots',
            'Die zappelnden Zitterale',
            'Die schnellen Schildkröten',
            'Die schicken Spinnen',
            'Die netten Nasenbären',
            'Die berühmten Bonobos',
            'Die tapferen Tucans',
            'Die klugen Krokodile',
            'Die wundervollen Wallabys',
            'Die kantigen Kaninchen',
            'Die allwissenden Aale',
            'Die erfahrenen Enten',
        ];
        foreach ($groupNames as $groupName) {
            $group = new Group();
            $group->name = $groupName;
            $group->event_id = $event->id;
            $group->save();
        }
    }

    /**
     * Run the "Stadtrallye" event seeds.
     *
     * @return void
     */
    public function runStadtrallye()
    {
        // check if event with name "Stadtrallye" exists
        $event = Event::where('name', 'Stadtrallye')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event();
        $event->name = 'Stadtrallye';
        $event->description = '<p>Die Stadtrallye ist ein Event, bei dem du in Gruppen die Stadt erkundest. Dabei gibt es verschiedene Aufgaben, die ihr lösen müsst. Dabei könnt ihr euch gegenseitig unterstützen und euch so besser kennenlernen.</p><p><strong>Treffpunkt: </strong> 9:00 Uhr Campus Eupener Straße</p>';
        $event->type = 'group_phase';
        $event->registration_from = new DateTime('2022-09-19 8:00:00');
        $event->registration_to = new DateTime('2022-09-20 9:15:00');
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
     * Run the "Hausführung Offline" event seeds.
     *
     * @return void
     */
    public function runHausfuehrungOffline()
    {
        // check if event with name "Hausführung (Präsenz)" exists
        $event = Event::where('name', 'Hausführung (Präsenz)')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event();
        $event->name = 'Hausführung (Präsenz)';
        $event->description = 'Nachdem ihr nun die Stadt erkundet habt, ist es Zeit auch mal eure Hochschule von innen zu sehen. In der Hausführung erwarten euch sowohl Informationen über wichtige Stationen am Campus, die ihr während eurer Studienzeit sicherlich das ein oder andere Mal aufsuchen werdet, als auch die Möglichkeit, einige eurer Professoren und ein paar ihrer Projekte kennenzulernen. Durch die Aufteilung nach Studiengang ist es auch eine gute Möglichkeit, schonmal Bekanntschaft mit euren Sitznachbarn in den Vorlesungen zu machen.';
        $event->type = 'group_phase';
        $event->registration_from = new DateTime('2022-09-19 8:00:00');
        $event->registration_to = new DateTime('2022-09-21 9:15:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 120;

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

        for ($i = 1; $i <= 10; $i++) {
            $groups[] = [
                'name' => "INF Hausführung $i",
                'course_id' => $coursesByAbbreviation['INF']->id,
            ];
        }
        for ($i = 1; $i <= 5; $i++) {
            $groups[] = [
                'name' => "ET Hausführung $i",
                'course_id' => $coursesByAbbreviation['ET']->id,
            ];
        }
        for ($i = 1; $i <= 3; $i++) {
            $groups[] = [
                'name' => "MCD Hausführung $i",
                'course_id' => $coursesByAbbreviation['MCD']->id,
            ];
        }
        for ($i = 1; $i <= 3; $i++) {
            $groups[] = [
                'name' => "WI Hausführung $i",
                'course_id' => $coursesByAbbreviation['WI']->id,
            ];
        }

        // save groups
        foreach ($groups as $groupData) {
            $group = new Group();
            $group->name = $groupData['name'];
            $group->event_id = $event->id;
            $group->course_id = $groupData['course_id'];
            $group->save();
        }
    }

    /**
     * Run the "Hausführung Online" event seeds.
     *
     * @return void
     */
    public function runHausfuehrungOnline()
    {
        // check if event with name "Hausführung (Online)" exists
        $event = Event::where('name', 'Hausführung (Online)')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event();
        $event->name = 'Hausführung (Online)';
        $event->description = 'Nachdem ihr nun die Stadt erkundet habt, ist es Zeit auch mal eure Hochschule von innen zu sehen. In der Online-Hausführung erwarten euch sowohl Informationen über wichtige Stationen am Campus, die ihr während eurer Studienzeit sicherlich das ein oder andere Mal aufsuchen werdet, als auch die Möglichkeit, einige eurer Professoren und ein paar ihrer Projekte kennenzulernen. Im Onlineformat werden jedoch nur Videos gezeigt und ggf. Fragen beantwortet.';
        $event->type = 'event_registration';
        $event->registration_from = new DateTime('2022-09-19 8:00:00');
        $event->registration_to = new DateTime('2022-09-21 9:15:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 121;

        // save the event
        $event->save();
    }

    /**
     * Run the "Kneipentour" event seeds.
     *
     * @return void
     */
    public function runKneipentour()
    {
        // check if event with name "Kneipentour" exists
        $event = Event::where('name', 'Kneipentour')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event();
        $event->name = 'Kneipentour';
        $event->description = null;
        $event->type = 'group_phase';
        $event->registration_from = new DateTime('2022-09-20 8:00:00');
        $event->registration_to = new DateTime('2022-09-21 17:50:00');
        $event->has_requirements = false;
        $event->consider_alcohol = true;
        $event->sort_order = 130;

        // save the event
        $event->save();

        // create event groups
        $groups = [];

        for ($i = 1; $i <= 30; $i++) {
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
        $event->description = '<p>Nachdem wir alle nach der Kneipentour am Mittwoch Abend etwas verkatert sind, gibt es doch nichts besseres als zusammen bei einem guten Fr&uuml;hst&uuml;ck auszukatern 😊 <br />Hierf&uuml;r bitte wir euch die 2&euro; Anmeldegeb&uuml;hr am Montag zwischen 13:00 und 14:30 Uhr oder Mittwoch zwischen 08:30 und 13:00 Uhr im FSR zu bezahlen, sonst k&ouml;nnt ihr leider nicht teilnehmen. </p>
        <p><strong>Wann:</strong> 22.09 ab 12:30 Uhr <br /><strong>Wo:</strong> FH, am D Geb&auml;ude <br /><strong>Was mitbringen:</strong> Tasse/ Becher und Teller ggf, Picknickdecke bei gutem Wetter.</p>
        <p>Im Anschluss k&ouml;nnen wir noch gemeinsam in den Park gehen und den Tag bei ein paar runden Flunkyball ausklingen lassen 😊</p>
        <p>Wir freuen uns auf euch</p>';
        $event->type = 'event_registration';
        $event->registration_from = new DateTime('2022-09-19 8:00:00');
        $event->registration_to = new DateTime('2022-09-21 12:00:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 140;
        $event->form = '[
            {
                "$formkit": "select",
                "name": "eating_habit",
                "label": "Essgewohnheit",
                "options": {
                    "vegetarian": "Ich esse vegetarisch",
                    "vegan": "Ich esse vegan",
                    "all": "Ich esse alles"
                },
                "placeholder": "Bitte auswählen",
                "validation": "required"
            }
        ]';

        // save the event
        $event->save();
    }

    /**
     * Run the "Sport" event seeds.
     *
     * @return void
     */
    public function runSport()
    {
        // check if event with name "Sport" exists
        $event = Event::where('name', 'Sport')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event();
        $event->name = 'Sport';
        $event->description = '<p>Auch sportliche Aktivitäten kommen bei uns nicht zu kurz. Für eine Anmeldegebühr von <strong>5€</strong> könnt ihr euch am Freitag auspowern.</p>
        <p>Bitte bezahlt die Anmeldegebühr am Montag oder Mittwoch im FSR. Solltet ihr bis Mittwoch nicht gezahlt haben, werden eure reservierten Plätze wieder freigegeben.</p>
        <p>Bitte beachtet auch die folgenden Hinweise zu den einzelnen Programmpunkten:</p>
        <p><strong>Fußball, Volleyball, Basketball:</strong> Die Anmeldegebühr dient als Pfand und wird euch zurückerstattet, wenn ihr Freitag erscheint.</p>
        <p>Anschließende Teilnahme am Kulturprogramm Punkt “Zoo” ist leider Zeitlich nicht möglich.</p>
        <p><strong>Yoga:</strong> Bitte bringt eine eigene Yogamatte mit.
        <p>Bouldern: Anschließende Teilnahme am Kulturprogramm Punkt “Zoo” ist leider Zeitlich nicht möglich.</p>
        <p><strong>Lasertag:</strong> Anschließende Teilnahme an den Kulturprogramm Punkten “Zoo” und “Stadtführung” ist leider Zeitlich nicht möglich.</p>
        <p>Die genauen Treffpunkte und Zeiten posten wir rechtzeitig im Telegram Info Channel.</p>
        <p>Wir freuen uns auf euch!</p>';
        $event->type = 'slot_booking';
        $event->registration_from = new DateTime('2022-09-19 08:00:00');
        $event->registration_to = new DateTime('2022-09-20 23:59:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 150;

        // save the event
        $event->save();

        // create event slots
        $slots = [
            [
                'name' => 'Fußball, Volleyball, Basketball',
                'has_requirements' => true,
                'maximum_participants' => 50,
            ],
            [
                'name' => 'Yoga',
                'has_requirements' => true,
                'maximum_participants' => 30,
            ],
            [
                'name' => 'Bouldern',
                'has_requirements' => true,
                'maximum_participants' => 50,
            ],
            [
                'name' => 'Lasertag',
                'has_requirements' => true,
                'maximum_participants' => 60,
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

    /**
     * Run the "Kultur" event seeds.
     *
     * @return void
     */
    public function runKultur()
    {
        // check if event with name "Kultur" exists
        $event = Event::where('name', 'Kultur')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event();
        $event->name = 'Kultur';
        $event->description = '<p>Die Stadt Aachen von einer etwas anderen Seite besser kennenlernen, Ziegen streicheln oder sich einfach den Bauch richtig voll schlagen? Auch das ist am Freitag in der Erstiwoche möglich.</p>
        <p>Bitte beachtet die folgenden Hinweise zu den einzelnen Programmpunkten:</p>
        <p><strong>Stadtführung:</strong> Die Stadtführung ist für euch kostenlos. Wenn ihr für Lasertag angemeldet seid, ist eine Teilnahme bei der Stadtführung leider <strong>nicht</strong> möglich.</p>
        <p><strong>Zoo:</strong> Den Eintrittspreis von <strong>4,10€</strong> müsst ihr vor Ort selber Zahlen Wenn ihr für Fußball, Volleyball, Basketball, Lasertag oder Bouldern angemeldet seid, ist eine Teilnahme bei der Stadtführung leider <strong>nicht</strong> möglich.</p>
        <p><strong>Kebaptour:</strong> Ihr müsst eure Döner/ Falafel-Taschen selber zahlen.</p>
        <p>Die genauen Treffpunkte und Zeiten posten wir rechtzeitig im Telegram Info Channel.</p>
        <p>Wir freuen uns auf euch!</p>';
        $event->type = 'slot_booking';
        $event->registration_from = new DateTime('2022-09-19 08:00:00');
        $event->registration_to = new DateTime('2022-09-20 23:59:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 151;

        // save the event
        $event->save();

        // create event slots
        $slots = [
            [
                'name' => 'Kebabtour',
                'has_requirements' => false,
                'maximum_participants' => null,
            ],
            [
                'name' => 'Stadtführung',
                'has_requirements' => false,
                'maximum_participants' => 15,
            ],
            [
                'name' => 'Zoo',
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
