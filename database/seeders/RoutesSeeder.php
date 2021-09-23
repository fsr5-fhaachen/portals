<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Grouphasstation;
use App\Models\Station;
use App\Models\Timeslot;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RoutesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // timeslots
        $csvFile = fopen(base_path('routes.csv'), 'r');
        $isFirstLine = true;
        $hhmmToTimeslotID = [];

        while (($data = fgetcsv($csvFile, 2000, ';')) !== FALSE) {
            if ($isFirstLine) {
                $isFirstLine = false;
            } else if ($data[2]) {

                // get time
                $time = Carbon::parse($data[2]);

                // create timeslot
                $timeslot = Timeslot::firstOrCreate([
                    'name' => $data[2],
                ], [
                    'time' => $time,
                ]);
                $hhmmToTimeslotID[$data[2]] = $timeslot->id;
            }
        }
        fclose($csvFile);

        // groups and stations
        $csvFile = fopen(base_path('routes.csv'), 'r');
        $isFirstLine = true;
        $colToStationId = [];

        while (($data = fgetcsv($csvFile, 2000, ';')) !== FALSE) {
            if ($isFirstLine) {
                $isFirstLine = false;

                // create stations
                for ($i = 3; $i < count($data); $i++) {
                    $station = Station::create([
                        'name' => $data[$i],
                    ]);
                    $colToStationId[$i] = $station->id;
                }
            } else {

                // create group
                $group = Group::create([
                    'course' => (!empty($data[1])) ? $data[1] : null,
                    'timeslot_id' => (!empty($hhmmToTimeslotID[$data[2]]) ? $hhmmToTimeslotID[$data[2]] : null),
                ]);

                // create groupHasStation
                for ($i = 3; $i < count($data); $i++) {
                    if (!empty(trim($data[$i])) && isset($colToStationId[$i])) {
                        Grouphasstation::create([
                            'group_id' => $group->id,
                            'station_id' => $colToStationId[$i],
                            'step' => $data[$i],
                        ]);
                    }
                }
            }
        }
        fclose($csvFile);
    }
}
