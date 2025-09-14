<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseEvent;
use App\Models\CourseGroup;
use App\Models\Event;
use App\Models\Group;
use App\Models\Slot;
use DateTime;
use Illuminate\Database\Seeder;

class EventsErstiwocheSeeder extends Seeder
{
    /**
     * Set path to the file with telegram data.
     *
     * @var string
     */
    private const TELEGRAM_CSV_PATH = __DIR__ . '/telegram.csv';

    /**
     * Run the events seeds.
     */
    public function run(): void
    {
        $telegram_links = $this->parseTelegramCsv();
        $this->runGruppenphase();
        $this->runGruppenphaseISMaster();
        $this->runStadtrallye($telegram_links);
        $this->runHausfuehrung();
        $this->runKneipentour($telegram_links);
        $this->runKaterbrunch();
        $this->runKultur();
        $this->runSport();
    }

    /**
     * Parse the 'telegram.csv' file.
     */
    private function parseTelegramCsv(): array
    {
        // check if the telegram file exists
        if (! file_exists(self::TELEGRAM_CSV_PATH)) {
            return [];
        }

        // read the telegram.csv file
        $data = array_map('str_getcsv', file(self::TELEGRAM_CSV_PATH));

        // remove the header row
        array_shift($data);

        // save telegram links
        $telegram_links = [];

        // loop through the data
        foreach ($data as $row) {
            $telegram_link = explode(';', $row[0]);

            $event_name = $telegram_link[0];
            $group_or_slot_name = $telegram_link[1];
            $link = $telegram_link[2];

            $telegram_links[$event_name][$group_or_slot_name] = $link;
        }

        return $telegram_links;
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
        $event = new Event;
        $event->name = 'Gruppenphase';
        $event->description = '<p>Während der Gruppenphase erhältst du von deinen Tutoren und Tutorinnen wichtige Informationen rund um das Studium. Außerdem ist die Gruppenphase dazu da, um direkt die anderen Erstis kennenzulernen und erste Freundschaften zu schließen.</p>';
        $event->type = 'group_phase';
        $event->registration_from = new DateTime('2025-09-22 8:00:00');
        $event->registration_to = new DateTime('2025-09-22 12:30:00');
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
            $group = new Group;
            $group->name = $groupName;
            $group->event_id = $event->id;
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
            'INF',
            'ET',
            'DIB',
            'MCD',
            'WI',
            'SBE',
            'ISE-Master',
            'ET-Master',
            'INF-Master',
            // only 'IS-Master' is excluded here
        ];

        // save courses
        foreach ($allowedCourses as $courseAbbreviation) {
            $course = $coursesByAbbreviation[$courseAbbreviation];
            $course_event = new CourseEvent;
            $course_event->course_id = $course->id;
            $course_event->event_id = $event->id;
            $course_event->save();
        }
    }

    /**
     * Run the "Gruppenphase" event seeds.
     */
    public function runGruppenphaseISMaster(): void
    {
        // check if event with name "Gruppenphase" exists
        $event = Event::where('name', 'Gruppenphase')->where('description', 'like', '%' . '<strong>M.Sc. Information Systems</strong>' . '%')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event;
        $event->name = 'Gruppenphase';
        $event->description = '<p>Während der Gruppenphase erhältst du von deinen Tutoren und Tutorinnen wichtige Informationen rund um das Studium. Außerdem ist die Gruppenphase dazu da, um direkt die anderen Erstis kennenzulernen und erste Freundschaften zu schließen.</p>
        <p>Diese Gruppenphase ist speziell für Studierende des Studiengangs <strong>M.Sc. Information Systems</strong>, da dort einige Besonderheiten erklärt werden.</p>';
        $event->type = 'group_phase';
        $event->registration_from = new DateTime('2025-09-22 8:00:00');
        $event->registration_to = new DateTime('2025-09-22 12:30:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 101;

        // save the event
        $event->save();

        // create event groups
        $groupNames = [
            'Die masterhaften Mammuts',
        ];
        foreach ($groupNames as $groupName) {
            $group = new Group;
            $group->name = $groupName;
            $group->event_id = $event->id;
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
            'IS-Master',
        ];

        // save courses
        foreach ($allowedCourses as $courseAbbreviation) {
            $course = $coursesByAbbreviation[$courseAbbreviation];
            $course_event = new CourseEvent;
            $course_event->course_id = $course->id;
            $course_event->event_id = $event->id;
            $course_event->save();
        }
    }

    /**
     * Run the "Mix and Mingle bei Mocktail und Bingo" event seeds.
     */
    public function runMocktail(): void
    {
        // check if event with name "Stadtrallye" exists
        $event = Event::where('name', 'Mix and Mingle bei Mocktail und Bingo')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event;
        $event->name = 'Mix and Mingle bei Mocktail und Bingo';
        $event->description = '<p>Geselliges Beisammensein mit Mocktails und einem kleinen Programm.</p>';
        $event->type = 'event_registration';
        $event->registration_from = new DateTime('2025-09-22 8:00:00');
        $event->registration_to = new DateTime('2025-09-22 16:00:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 105;

        // save the event
        $event->save();
    }

    /**
     * Run the "Stadtrallye" event seeds.
     */
    public function runStadtrallye(array $telegram_links): void
    {
        // check if event with name "Stadtrallye" exists
        $event = Event::where('name', 'Stadtrallye')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event;
        $event->name = 'Stadtrallye';
        $event->description = '<p>Die Stadtrallye ist ein Event, bei dem du in Gruppen die Stadt erkundest. Dabei gibt es verschiedene Aufgaben, die ihr lösen müsst. Dabei könnt ihr euch gegenseitig unterstützen und euch so besser kennenlernen.</p><p><strong>Treffpunkt: </strong> 9:00 Uhr Campus Eupener Straße</p>';
        $event->type = 'group_phase';
        $event->registration_from = new DateTime('2025-09-22 8:00:00');
        $event->registration_to = new DateTime('2025-09-23 09:45:00');
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
                'telegram_group_link' => $telegram_links[$event->name]["Gruppe $i"] ?? null,
            ];
        }

        // save groups
        foreach ($groups as $groupData) {
            $group = new Group;
            $group->name = $groupData['name'];
            $group->event_id = $event->id;
            $group->telegram_group_link = array_key_exists('telegram_group_link', $groupData) ? $groupData['telegram_group_link'] : null;
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
        $event = new Event;
        $event->name = 'Hausführung';
        $event->description = '<p>Nachdem ihr nun die Stadt erkundet habt, ist es Zeit auch mal eure Hochschule von innen zu sehen. In der Hausführung erwarten euch sowohl Informationen über wichtige Stationen am Campus, die ihr während eurer Studienzeit sicherlich das ein oder andere Mal aufsuchen werdet, als auch die Möglichkeit, einige eurer Professoren und ein paar ihrer Projekte kennenzulernen. Durch die Aufteilung nach Studiengang ist es auch eine gute Möglichkeit, schonmal Bekanntschaft mit euren Sitznachbarn in den Vorlesungen zu machen.</p>';
        $event->type = 'group_phase';
        $event->registration_from = new DateTime('2025-09-22 8:00:00');
        $event->registration_to = new DateTime('2025-09-24 9:30:00');
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
                'course_ids' => [
                    $coursesByAbbreviation['INF']->id,
                    $coursesByAbbreviation['ISE-Master']->id,
                    $coursesByAbbreviation['SBE']->id
                ],
            ];
        }
        for ($i = 1; $i <= 4; $i++) {
            $groups[] = [
                'name' => "ET Hausführung $i",
                'course_ids' => [
                    $coursesByAbbreviation['ET']->id,
                    $coursesByAbbreviation['ET-Master']->id
                ],
            ];
        }
        for ($i = 1; $i <= 3; $i++) {
            $groups[] = [
                'name' => "DIB Hausführung $i",
                'course_ids' => [
                    $coursesByAbbreviation['DIB']->id,
                    $coursesByAbbreviation['MCD']->id
                ],
            ];
        }
        for ($i = 1; $i <= 3; $i++) {
            $groups[] = [
                'name' => "WI Hausführung $i",
                'course_ids' => [
                    $coursesByAbbreviation['WI']->id,
                ],
            ];
        }

        for ($i = 1; $i <= 1; $i++) {
            $groups[] = [
                'name' => "IS-Master Hausführung $i",
                'course_ids' => [
                    $coursesByAbbreviation['IS-Master']->id
                ],
            ];
        }

        for ($i = 1; $i <= 1; $i++) {
            $groups[] = [
                'name' => "INF-Master Hausführung $i",
                'course_ids' => [
                    $coursesByAbbreviation['INF-Master']->id
                ],
            ];
        }

        // save groups
        foreach ($groups as $groupData) {
            $group = new Group;
            $group->name = $groupData['name'];
            $group->event_id = $event->id;
            $group->save();

            // save course_group collections
            foreach ($groupData['course_ids'] as $course_id) {
                $course_group = new CourseGroup;
                $course_group->course_id = $course_id;
                $course_group->group_id = $group->id;
                $course_group->save();
            }
        }
    }

    /**
     * Run the "Kneipentour" event seeds.
     */
    public function runKneipentour(array $telegram_links): void
    {
        // check if event with name "Kneipentour" exists
        $event = Event::where('name', 'Kneipentour')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event;
        $event->name = 'Kneipentour';
        $event->description = '<p>Sei Teil unserer Kneipentour, um die besten Bars zu entdecken, unterhaltsame Spiele zu genießen und deine Kommilitonen kennenzulernen.</p>';
        $event->type = 'group_phase';
        $event->registration_from = new DateTime('2025-09-22 8:00:00');
        $event->registration_to = new DateTime('2025-09-24 17:00:00');
        $event->has_requirements = false;
        $event->consider_alcohol = true;
        $event->sort_order = 130;

        // save the event
        $event->save();

        // create event groups
        $groups = [];

        for ($i = 1; $i <= 24; $i++) {
            $groups[] = [
                'name' => "Gruppe $i",
                'telegram_group_link' => $telegram_links[$event->name]["Gruppe $i"] ?? null,
            ];
        }

        // save groups
        foreach ($groups as $groupData) {
            $group = new Group;
            $group->name = $groupData['name'];
            $group->event_id = $event->id;
            $group->telegram_group_link = array_key_exists('telegram_group_link', $groupData) ? $groupData['telegram_group_link'] : null;
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
        $event = new Event;
        $event->name = 'Katerbrunch';
        $event->description = '<p>Nachdem wir alle nach der Kneipentour am Mittwoch Abend etwas verkatert sind, gibt es doch nichts besseres als zusammen bei einem guten Fr&uuml;hst&uuml;ck auszukatern 😊 <br />Hierf&uuml;r bitte wir euch die 3&euro; Anmeldegeb&uuml;hr am Montag zwischen 12:30 und 14:00 Uhr, Mittwoch zwischen 10:00 und 14:00 Uhr im FSR oder vor Ort zu bezahlen, sonst k&ouml;nnt ihr leider nicht teilnehmen.</p>
        <p><strong>Wann:</strong> 25.09. ab 12:30 Uhr <br /><strong>Wo:</strong> FH, am D Geb&auml;ude <br /><strong>Was mitbringen:</strong> Tasse/ Becher und Teller ggf, Picknickdecke bei gutem Wetter.</p>
        <p>Im Anschluss findet noch ein spannender Spieleabend mit Brettspielen und Quizshow statt.</p> <br/>
        <p>Wir freuen uns auf euch</p>';
        $event->type = 'event_registration';
        $event->registration_from = new DateTime('2025-09-22 8:00:00');
        $event->registration_to = new DateTime('2025-09-25 12:20:00');
        $event->has_requirements = true;
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
        $event = new Event;
        $event->name = 'Sport';
        $event->description = '<p>Auch sportliche Aktivitäten kommen bei uns nicht zu kurz. Ihr könnt euch am Freitag richtig auspowern.</p>
            <p>Bitte beachtet auch die folgenden Hinweise zu den einzelnen Programmpunkten:</p>
            <p><strong>Hochschulport:</strong> Hattet Ihr schon genug Saufsport diese Woche und wollt mal wieder richtigen Sport machen? Dann kommt mit uns zum Sportzentrum Königshügel! Egal ob Ihr Lust auf Fußball, Volleyball oder Basketball habt, dort gibt es alles. Nebenbei lernt Ihr den zentralen Ort für den Aachener Hochschulsport kennen und könnt euch über weitere Angebote und Events informieren.</p><br>
            <p><strong>Yoga:</strong> Trinken oder nicht trinken, das ist hier nicht die Frage. Stelle deine Yogafähigkeiten beim herabschauenden Hund oder beim Krieger 2 unter Beweis. Mit oder ohne Spaßgetränk, hier kannst du zeigen, dass in dir mehr Beweglichkeit steckt, als in deinem Bürostuhl.</p><br>
            <p><strong>Bouldern:</strong> Einfach losklettern! Bouldern ist Klettern in Absprunghöhe - keine Vorkenntnisse nötig, nur Neugier und ein bisschen Abenteuerlust. Gemeinsam tüfteln, Routen ausprobieren und Erfolge feiern macht dabei besonders viel Spaß. Perfekt, um dich auszupowern, Neues auszuprobieren und vielleicht deine neue Lieblingssportart zu entdecken.
            <p>Was du brauchst: Getränk, Bussticket, Studentennachweis, Hallenschuhe oder Boulderschuhe.
            Boulderschuhe können auch vor Ort auf eigene Kosten entliehen werden. 
            <p><strong>Anmeldegebühr: 5€</strong></p></p></p><br>
            <p><strong>Lasertag:</strong> Beim Lasertag kannst du dein Aim unter Beweis stellen und den anderen zeigen das du nicht nur Online zielen kannst.
            <p><strong>Anmeldegebühr: 5€</strong></p></p><br>
            <p><strong>Allgemein:</strong> Anmeldegebühren zahlt ihr bitte am Montag zwischen 12:30 und 14:00 Uhr oder am Mittwoch zwischen 10:00 und 14:00 Uhr im FSR. Solltet ihr bis Mittwoch nicht gezahlt haben, werden eure reservierten Plätze wieder freigegeben.</p>
            <p>Wer bei Trinkyoga mitmacht, kann sich auch zur Foodtour unter "Kultur" anmelden. Andere Kombinationen sind zeitlich leider nicht möglich.</p>
            <p>Die genauen Treffpunkte und Zeiten posten wir rechtzeitig im Telegram Info Channel.</p>
            <p>Wir freuen uns auf euch!</p>';
        $event->type = 'slot_booking';
        $event->registration_from = new DateTime('2025-09-22 08:00:00');
        $event->registration_to = new DateTime('2025-09-24 23:59:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 150;

        // save the event
        $event->save();

        // create event slots
        $slots = [
            [
                'name' => 'Hochschulsport',
                'has_requirements' => false,
                'maximum_participants' => 50,
            ],
            [
                'name' => 'Bouldern',
                'has_requirements' => true,
                'maximum_participants' => 57,
            ],
            [
                'name' => 'Trinkyoga',
                'has_requirements' => false,
                'maximum_participants' => 30,
            ],
            [
                'name' => 'Lasertag',
                'has_requirements' => true,
                'maximum_participants' => 50,
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
        $event = new Event;
        $event->name = 'Kultur';
        $event->description = '<p>Den Tivoli erkunden, Ziegen streicheln oder sich einfach den Bauch richtig voll schlagen?
            Auch das ist am Freitag in der Erstiwoche möglich.</p>
            <p>Bitte beachtet die folgenden Hinweise zu den einzelnen Programmpunkten:</p>
            <p><strong>Tivoli-Tour:</strong> Bei der Stadiontour durch das Alemannia Aachen Stadion hast du exklusiv die Möglichkeit, einen Blick hinter die Kulissen zu werfen. Du erlebst hautnah Bereiche, die sonst nur Spielern oder Sponsoren vorbehalten sind - und das sogar kostenlos! Am Ende der Tour erwartet alle Teilnehmer*innen noch eine Überraschung. Also, worauf wartest du noch?</p><br>
            <p><strong>Tierpark:</strong> Egal, ob Aachener oder nicht, der Aachener Tierpark bietet euch ein tolles Erlebnis vor MensaBeats. Entspannt coole Tiere ansehen und Kindheitserinnerungen wecken oder einen weiteren Teil der Aachener Kultur entdecken. Sei dabei und lass den Aachener Tierpark das vorletzte Erlebnis deiner Ersti-Woche werden!</p><br>
            <p><strong>Foodtour:</strong> Bist du neu in Aachen und willst wissen wo man nach den Vorlesungen etwas Leckeres zu Essen findet? Oder hast du einfach Lust dich durch die verschiedenen Restaurants und Buden Aachens zu probieren? Dann ist die Foodtour genau das Richtige für dich! Zieh mit uns los und lerne Aachener Spezialitäten und andere leckere und besondere Speisen kennen.</p><br>
            <p><strong>Allgemein:</strong> Wer bei der Foodtour mitmacht, kann sich auch zum Trinkyoga unter "Sport" anmelden. Andere Kombinationen sind zeitlich leider nicht möglich.</p>
            <p>Die genauen Treffpunkte und Zeiten posten wir rechtzeitig im Telegram Info Channel.</p>
            <p>Wir freuen uns auf euch!</p>';
        $event->type = 'slot_booking';
        $event->registration_from = new DateTime('2025-09-22 08:00:00');
        $event->registration_to = new DateTime('2025-09-24 23:59:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 151;
        // save the event
        $event->save();

        // create event slots
        $slots = [
            [
                'name' => 'Tivoli-Tour',
                'has_requirements' => false,
                'maximum_participants' => 15,
            ],
            [
                'name' => 'Tierpark',
                'has_requirements' => false,
                'maximum_participants' => 20,
            ],
            [
                'name' => 'Foodtour',
                'has_requirements' => false,
                'maximum_participants' => 60,
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
