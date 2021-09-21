<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Grouphasstation;
use App\Models\Station;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class TutorStationController extends Controller
{
    public function index(Request $request, $id)
    {
        $station = Station::find($id);
        $groupsBySteps = [];

        $groupHasStation = Grouphasstation::where('station_id', $id)->orderBy('step')->get();
        foreach ($groupHasStation as $groupHasStation) {
            if (!key_exists($groupHasStation->step, $groupsBySteps)) {
                $groupsBySteps[$groupHasStation->step] = [
                    'step' => $groupHasStation->step,
                    'groups' => [],
                ];
            }
            $groupsBySteps[$groupHasStation->step]['groups'][] = [
                'id' => $groupHasStation->id,
                'done' => $groupHasStation->done,
                'group' => $groupHasStation->group,
            ];
        }
        ksort($groupsBySteps);

        return Inertia::render('Tutor/Station/Index', [
            'station' => $station,
            'groupsBySteps' => $groupsBySteps,
        ]);
    }

    public function join(Request $request, $id)
    {
        // get tutor
        $tutor = Tutor::find($request->session()->get('tutor'));
        $tutor->group_id = null;
        $tutor->station_id = $id;
        $tutor->save();

        // redirect to group page
        return Redirect::to('/tutor/station/' . $id);
    }
}
