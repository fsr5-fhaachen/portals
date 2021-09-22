<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Grouphasstation;
use App\Models\Student;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class TutorGroupController extends Controller
{
    public function index(Request $request, $id)
    {
        $group = Group::find($id);
        return Inertia::render('Tutor/Group/Index', [
            'group' => $group,
            'students' => $group->students()->orderBy('firstname')->get(),
            'stations' => $group->stations()->orderBy('step')->get(),
            'isAdmin' => (bool)Tutor::find($request->session()->get('tutor'))->is_admin,
        ]);
    }

    public function join(Request $request, $id)
    {
        // get tutor
        $tutor = Tutor::find($request->session()->get('tutor'));
        $tutor->group_id = $id;
        $tutor->station_id = null;
        $tutor->save();

        // redirect to group page
        return Redirect::to('/tutor/group/' . $id);
    }

    public function studentAttended($id)
    {
        // update student
        $student = Student::find($id);
        $student->attended = True;
        $student->save();
    }

    public function studentUnattended($id)
    {
        // update student
        $student = Student::find($id);
        $student->attended = False;
        $student->save();
    }

    public function stationDone($id)
    {
        // update groupHasStation
        $station = Grouphasstation::find($id);
        $station->done = True;
        $station->save();
    }

    public function stationUndone($id)
    {
        // update groupHasStation
        $station = Grouphasstation::find($id);
        $station->done = False;
        $station->save();
    }


    public function getTutorGroupInfo($tutorId)
    {
        // TODO implement retrieving the group and maybe the necessary information for specified tutor
    }


    /**
     * Gets a Student model from the database by its ID and updates the student_attendance.
     *
     * @param mixed $studentId
     * @param mixed $value The value that gets assigned to attended of specified student. Should be true or false.
     */
    public function setStudentAttendance($studentId, $value)
    {
        Student::find($studentId)->update(['attended' => $value]);
    }

    /**
     * Gets a Group model from the database by its ID and updates the station_id.
     *
     * @param mixed $groupId
     * @param mixed $stationId
     */
    public function setGroupStation($groupId, $stationId)
    {
        Group::find($groupId)->update(['station_id' => $stationId]);
    }
}
