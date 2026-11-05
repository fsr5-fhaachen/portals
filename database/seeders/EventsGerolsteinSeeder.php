<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Group;
use App\Models\Registration;
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
        $this->runWorkshops();
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
     * Run the "Spieleolympiade" event seeds (friday from 10:00). The students register themselves until the morning, so
     * the teams can be divided before the start.
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
        $event->registration_from = new DateTime('2026-11-05 8:00:00');
        $event->registration_to = new DateTime('2026-11-06 9:00:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 110;

        // save the event
        $event->save();

        // create event groups
        $groupNames = [
            'Magenta/Pink',
            'Violet',
            'Türkis/Cyangrün',
            'Silber',
            'Gold',
            'Schwarz',
            'Grasgrün',
            'Neongelb/Gelbgrün',
            'Minz-/Kiwigrün',
            'Gelb',
            'Weinrot',
            'Senfgelb',
            'Orange',
            'Blau',
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
     * Run the "Workshops" event seeds (saturday). The students register themselves with their priorities.
     */
    public function runWorkshops(): void
    {
        // check if event with name "Workshops" exists
        $event = Event::where('name', 'Workshops')->first();
        if ($event) {
            return;
        }

        // create a new event
        $event = new Event;
        $event->name = 'Workshops';
        $event->type = 'workshop_booking';
        $event->registration_from = new DateTime('2026-11-05 8:00:00');
        $event->registration_to = new DateTime('2026-11-06 20:00:00');
        $event->has_requirements = false;
        $event->consider_alcohol = false;
        $event->sort_order = 210;
        // more priorities than blocks are replacement wishes, if a workshop is full
        $event->workshop_priority_count = 5;

        // save the event
        $event->save();

        // create the blocks
        $blocks = [
            'morning' => ['name' => 'Vormittag', 'starts_at' => '2026-11-07 10:00:00', 'ends_at' => '2026-11-07 12:00:00'],
            'afternoon' => ['name' => 'Nachmittag', 'starts_at' => '2026-11-07 13:30:00', 'ends_at' => '2026-11-07 16:00:00'],
            'evening' => ['name' => 'Spätnachmittag', 'starts_at' => '2026-11-07 16:30:00', 'ends_at' => '2026-11-07 18:00:00'],
        ];
        foreach ($blocks as $key => $blockData) {
            $blocks[$key] = $event->workshopBlocks()->create($blockData);
        }

        // create the workshops with their sessions, the location of the session by block
        $workshops = [
            [
                'name' => '3D-Design & 3D-Druck',
                'description' => 'Designe deine eigenen 3D-Objekte und drucke sie mit PLA aus.',
                'sessions' => ['morning' => 'Ragecageraum', 'afternoon' => 'Ragecageraum', 'evening' => 'Ragecageraum'],
                'minimum' => 4,
                'maximum' => 16,
            ],
            [
                'name' => 'Rätselraum',
                'description' => 'Löst im Team knifflige Rätsel und tretet gegeneinander an.',
                'sessions' => ['morning' => 'Spieleraum', 'afternoon' => 'Spieleraum', 'evening' => 'Spieleraum'],
                'minimum' => 4,
                'maximum' => 14,
            ],
            [
                'name' => 'Last One Laughing',
                'description' => 'Wer lacht, fliegt raus. Wer bleibt am längsten ernst?',
                'sessions' => ['morning' => 'Karaokeraum', 'afternoon' => 'Karaokeraum'],
                'minimum' => 5,
                'maximum' => 12,
            ],
            [
                'name' => 'Getränkeyoga',
                'description' => 'Erst trinken, dann Yoga und danach noch Spiele wie Ninja.',
                'sessions' => ['evening' => 'Karaokeraum'],
                'minimum' => 1,
                'maximum' => 10,
            ],
            [
                'name' => 'Schlüsselanhänger basteln',
                'description' => 'Gestalte deinen eigenen Schlüsselanhänger aus Paracord, Fimo-Knete und Murmeln.',
                'sessions' => ['morning' => 'Partyraum 1'],
                'minimum' => 1,
                'maximum' => 15,
            ],
            [
                'name' => 'Schmuck aus Draht',
                'description' => 'Biege dir deinen eigenen Schmuck aus Draht.',
                'sessions' => ['afternoon' => 'Partyraum 1'],
                'minimum' => 1,
                'maximum' => 15,
            ],
            [
                'name' => 'Armbänder knüpfen',
                'description' => 'Knüpfe dir Freundschaftsarmbänder aus Baumwollgarn.',
                'sessions' => ['evening' => 'Partyraum 1'],
                'minimum' => 1,
                'maximum' => 15,
            ],
            [
                'name' => 'Kartoffeldruck',
                'description' => 'Schnitze Stempel aus Kartoffeln und drucke damit deine eigenen Motive.',
                'sessions' => ['morning' => 'Partyraum 2'],
                'minimum' => 1,
                'maximum' => 15,
            ],
            [
                'name' => 'Origami',
                'description' => 'Lerne, aus einem Blatt Papier Figuren zu falten.',
                'sessions' => ['afternoon' => 'Partyraum 2'],
                'minimum' => 1,
                'maximum' => 15,
            ],
            [
                'name' => 'Zeichenkurs',
                'description' => 'Lerne neue Zeichentechniken und bring deine Ideen aufs Papier.',
                'sessions' => ['evening' => 'Partyraum 2'],
                'minimum' => 1,
                'maximum' => 15,
            ],
            [
                'name' => 'Collagen gestalten',
                'description' => 'Gestalte Collagen aus alten Zeitschriften.',
                'sessions' => ['morning' => 'Partyraum 3'],
                'minimum' => 1,
                'maximum' => 15,
            ],
            [
                'name' => 'Spielkarten gestalten',
                'description' => 'Gestalte dein eigenes Kartenspiel auf Blanko-Karten.',
                'sessions' => ['afternoon' => 'Partyraum 3'],
                'minimum' => 1,
                'maximum' => 15,
            ],
            [
                'name' => 'Deko-Workshop',
                'description' => 'Bastle Deko für Gerolstein oder zum Mitnehmen.',
                'sessions' => ['evening' => 'Partyraum 3'],
                'minimum' => 1,
                'maximum' => 15,
            ],
            [
                'name' => 'Diskussionsrunde',
                'description' => 'Diskutiere über verschiedenste Themen, von ernst bis absurd.',
                'sessions' => ['morning' => 'Feuerhütte'],
                'minimum' => 6,
                'maximum' => 12,
            ],
            [
                'name' => 'Pfadfinder 101',
                'description' => 'Lerne Knoten und weitere Grundlagen der Pfadfinderei.',
                'sessions' => ['afternoon' => 'Feuerhütte', 'evening' => 'Feuerhütte'],
                'minimum' => 4,
                'maximum' => 10,
            ],
            [
                'name' => 'Speeddating for Friends',
                'description' => 'Ihr sitzt zu zweit und bekommt alle 10 Minuten eine neue Gesprächspartnerin oder einen neuen Gesprächspartner. Witzige Fragen helfen beim Einstieg.',
                'sessions' => ['morning' => 'Speisesaal (Bistrobereich)', 'afternoon' => 'Speisesaal (Bistrobereich)', 'evening' => 'Speisesaal (Bistrobereich)'],
                'minimum' => 8,
                'maximum' => 18,
            ],
            [
                'name' => 'Dungeons & Dragons',
                'description' => 'Erlebe ein Pen-&-Paper-Abenteuer in einer Runde Dungeons & Dragons.',
                'sessions' => ['morning' => 'Speisesaal (Essensausgabe)', 'afternoon' => 'Speisesaal (Essensausgabe)', 'evening' => 'Speisesaal (Essensausgabe)'],
                'minimum' => 3,
                'maximum' => 6,
            ],
            [
                'name' => 'Fotoworkshop',
                'description' => 'Lerne Komposition und Bildgestaltung mit deinem Handy.',
                'sessions' => ['morning' => 'Photobox', 'afternoon' => 'Photobox', 'evening' => 'Photobox'],
                'minimum' => 4,
                'maximum' => 16,
            ],
            [
                'name' => 'Volleyball',
                'description' => 'Halbwegs professionelles Volleyball-Training.',
                'sessions' => ['morning' => 'Volleyballfeld'],
                'minimum' => 6,
                'maximum' => 20,
            ],
            [
                'name' => 'Fußball',
                'description' => 'Halbwegs professionelles Fußball-Training.',
                'sessions' => ['afternoon' => 'Fußballplatz'],
                'minimum' => 6,
                'maximum' => 20,
            ],
            [
                'name' => 'Ultimate Frisbee',
                'description' => 'Lerne Ultimate Frisbee kennen und spiel im Team.',
                'sessions' => ['evening' => 'Fußballplatz'],
                'minimum' => 6,
                'maximum' => 20,
            ],
            [
                'name' => 'Brücken- & Turmbau-Wettbewerb',
                'description' => 'Wer baut die stabilste Brücke oder den höchsten Turm?',
                'sessions' => ['morning' => 'Tischtennisplatten', 'afternoon' => 'Tischtennisplatten', 'evening' => 'Tischtennisplatten'],
                'minimum' => 4,
                'maximum' => 18,
            ],
        ];

        foreach ($workshops as $workshopData) {
            $workshop = $event->workshops()->create([
                'name' => $workshopData['name'],
                'description' => $workshopData['description'],
            ]);

            foreach ($workshopData['sessions'] as $blockKey => $location) {
                $workshop->sessions()->create([
                    'workshop_block_id' => $blocks[$blockKey]->id,
                    'location' => $location,
                    'minimum_participants' => $workshopData['minimum'],
                    'maximum_participants' => $workshopData['maximum'],
                ]);
            }
        }
    }
}
