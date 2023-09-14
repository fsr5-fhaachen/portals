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
     */
    public function run(): void
    {
        $this->runGruppenphase();
        $this->runStadtrallye();
        $this->runHausfuehrung();
        $this->runKneipentour();
        $this->runKaterbrunch();
        $this->runKultur();
        $this->runSport();
    }

    /**
     * Run the "Gruppenphase" event seeds.
     */
    public function runGruppenphase(): void
    {
        // check if event with name "Gruppenphase" exists
        $event = Event::where('name', 'Gruppenphase')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event();
        $event->name = 'Gruppenphase';
        $event->description = '<p>Während der Gruppenphase erhältst du von deinen Tutoren und Tutorinnen wichtige Informationen rund um das Studium. Außerdem ist die Gruppenphase dazu da, um direkt die anderen Erstis kennenzulernen und erste Freundschaften zu schließen.</p>';
        $event->type = 'group_phase';
        $event->registration_from = new DateTime('2023-09-25 8:00:00');
        $event->registration_to = new DateTime('2023-09-25 12:30:00');
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
            'Die putzigen Pandas',
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
     */
    public function runStadtrallye(): void
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
        $event->registration_from = new DateTime('2023-09-25 8:00:00');
        $event->registration_to = new DateTime('2023-09-26 9:15:00');
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
     * Run the "Hausführung" event seeds.
     */
    public function runHausfuehrung(): void
    {
        // check if event with name "Hausführung" exists
        $event = Event::where('name', 'Hausführung')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event();
        $event->name = 'Hausführung';
        $event->description = '<p>Nachdem ihr nun die Stadt erkundet habt, ist es Zeit auch mal eure Hochschule von innen zu sehen. In der Hausführung erwarten euch sowohl Informationen über wichtige Stationen am Campus, die ihr während eurer Studienzeit sicherlich das ein oder andere Mal aufsuchen werdet, als auch die Möglichkeit, einige eurer Professoren und ein paar ihrer Projekte kennenzulernen. Durch die Aufteilung nach Studiengang ist es auch eine gute Möglichkeit, schonmal Bekanntschaft mit euren Sitznachbarn in den Vorlesungen zu machen.</p>';
        $event->type = 'group_phase';
        $event->registration_from = new DateTime('2023-09-25 8:00:00');
        $event->registration_to = new DateTime('2023-09-27 9:15:00');
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

        for ($i = 1; $i <= 8; $i++) {
            $groups[] = [
                'name' => "INF Hausführung $i",
                'course_id' => $coursesByAbbreviation['INF']->id,
            ];
        }
        for ($i = 1; $i <= 2; $i++) {
            $groups[] = [
                'name' => "ET Hausführung $i",
                'course_id' => $coursesByAbbreviation['ET']->id,
            ];
        }
        for ($i = 1; $i <= 3; $i++) {
            $groups[] = [
                'name' => "DIB Hausführung $i",
                'course_id' => $coursesByAbbreviation['DIB']->id,
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
     * Run the "Kneipentour" event seeds.
     */
    public function runKneipentour(): void
    {
        // check if event with name "Kneipentour" exists
        $event = Event::where('name', 'Kneipentour')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event();
        $event->name = 'Kneipentour';
        $event->description = '<p>Sei Teil unserer Kneipentour, um die besten Bars zu entdecken, unterhaltsame Spiele zu genießen und deine Kommilitonen kennenzulernen.</p>';
        $event->type = 'group_phase';
        $event->registration_from = new DateTime('2023-09-26 8:00:00');
        $event->registration_to = new DateTime('2023-09-27 17:50:00');
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
     */
    public function runKaterbrunch(): void
    {
        // check if event with name "Katerbrunch" exists
        $event = Event::where('name', 'Katerbrunch')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event();
        $event->name = 'Katerbrunch';
        $event->description = '<p>Nachdem wir alle nach der Kneipentour am Mittwoch Abend etwas verkatert sind, gibt es doch nichts besseres als zusammen bei einem guten Fr&uuml;hst&uuml;ck auszukatern 😊 <br />Hierf&uuml;r bitte wir euch die 2&euro; Anmeldegeb&uuml;hr am Montag zwischen 13:00 und 14:30 Uhr oder Mittwoch zwischen 08:30 und 13:00 Uhr im FSR zu bezahlen, sonst k&ouml;nnt ihr leider nicht teilnehmen.</p>
        <p><strong>Wann:</strong> 28.09 ab 12:30 Uhr <br /><strong>Wo:</strong> FH, am D Geb&auml;ude <br /><strong>Was mitbringen:</strong> Tasse/ Becher und Teller ggf, Picknickdecke bei gutem Wetter.</p>
        <p>Im Anschluss k&ouml;nnen wir noch gemeinsam in den Park gehen und den Tag bei ein paar runden Flunkyball ausklingen lassen 😊</p>
        <p>Wir freuen uns auf euch</p>';
        $event->type = 'event_registration';
        $event->registration_from = new DateTime('2023-09-25 8:00:00');
        $event->registration_to = new DateTime('2023-09-26 23:59:59');
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
     */
    public function runSport(): void
    {
        // create a new event
        $event = new Event();
        $event->name = 'Sport';
        $event->description = '<p>Auch sportliche Aktivitäten kommen bei uns nicht zu kurz. Für eine Anmeldegebühr von <strong>5€</strong> könnt ihr euch am Freitag auspowern.</p>
            <p>Bitte bezahlt die Anmeldegebühr am Montag oder Mittwoch im FSR. Solltet ihr bis Mittwoch nicht gezahlt haben, werden eure reservierten Plätze wieder freigegeben.</p>
            <p>Bitte beachtet auch die folgenden Hinweise zu den einzelnen Programmpunkten:</p>
            <p><strong>Fußball, Volleyball, Basketball:</strong> Die Anmeldegebühr bekommt ihr zurückerstattet, wenn es wegen schlechtem Wetter ausfällt.</p>
            <p><strong>Yoga:</strong> Bitte bringt eine eigene Yogamatte mit.</p>
            <p><strong>Allgemein:</strong> Anschließende Teilnahme an den Kulturprogrammpunkten ist nur die “Kebabtour” zeitlich möglich.</p>
            <p>Die genauen Treffpunkte und Zeiten posten wir rechtzeitig im Telegram Info Channel.</p>
            <p>Wir freuen uns auf euch!</p>';
        $event->type = 'slot_booking';
        $event->registration_from = new DateTime('2023-09-25 08:00:00');
        $event->registration_to = new DateTime('2023-09-26 23:59:00');
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
                'maximum_participants' => 30,
            ],
            [
                'name' => 'Lasertag',
                'has_requirements' => true,
                'maximum_participants' => 57,
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
     */
    public function runKultur(): void
    {
        // check if event with name "Kultur" exists
        $event = Event::where('name', 'Kultur')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event();
        $event->name = 'Kultur';
        $event->description = '<p>Die Stadt Aachen von einer etwas anderen Seite besser kennenlernen, Ziegen streicheln oder sich einfach den Bauch richtig voll schlagen?
            Auch das ist am Freitag in der Erstiwoche möglich.</p>
            <p>Bitte beachtet die folgenden Hinweise zu den einzelnen Programmpunkten:</p>
            <p><strong>Tour durch das Alemaniastadion:</strong> Die Stadiontour ist für euch kostenlos.</p>
            <p><strong>Stadtführung:</strong> Die Stadtführung ist für euch kostenlos.</p>
            <p><strong>Zoo:</strong> Den Eintrittspreis von <strong>4,10€</strong> müsst ihr vor Ort selber zahlen.</p>
            <p><strong>Kebabtour:</strong> Ihr müsst eure Döner/ Falafel-Taschen selber zahlen.</p>
            <p><strong>Allgemein:</strong> Anschließende Teilnahme weiteren Programmpunkten ist nur die “Kebabtour” zeitlich möglich.</p>
            <p>Die genauen Treffpunkte und Zeiten posten wir rechtzeitig im Telegram Info Channel.</p>
            <p>Wir freuen uns auf euch!</p>';
        $event->type = 'slot_booking';
        $event->registration_from = new DateTime('2023-09-25 08:00:00');
        $event->registration_to = new DateTime('2023-09-26 23:59:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 151;
        // save the event
        $event->save();

        // create event slots
        $slots = [
            [
                'name' => 'Tour durch das Alemaniastadion',
                'has_requirements' => false,
                'maximum_participants' => 200,
            ],
            [
                'name' => 'Kebabtour',
                'has_requirements' => false,
                'maximum_participants' => null,
            ],
            [
                'name' => 'Stadtführung',
                'has_requirements' => false,
                'maximum_participants' => 20,
            ],
            [
                'name' => 'Zoo',
                'has_requirements' => false,
                'maximum_participants' => 15,
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
