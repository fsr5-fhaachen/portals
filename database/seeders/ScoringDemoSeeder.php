<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\CompetitionTeam;
use App\Models\Event;
use Illuminate\Database\Seeder;

class ScoringDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // check if the demo competition already exists
        if (Competition::where('name', 'Stadtrallye')->exists()) {
            return;
        }

        $event = Event::where('name', 'group_phase')->first();

        // create competition
        $competition = new Competition;
        $competition->name = 'Stadtrallye';
        $competition->event_id = $event?->id;
        $competition->is_open = true;
        $competition->save();

        // create a team for each group of the event
        foreach ($event?->groups()->orderBy('id')->get() ?? [] as $group) {
            $team = new CompetitionTeam;
            $team->competition_id = $competition->id;
            $team->group_id = $group->id;
            $team->name = $group->name;
            $team->save();
        }
    }
}
