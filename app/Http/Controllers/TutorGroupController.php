<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Student;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class TutorGroupController extends Controller
{
    public function index($id)
    {
        return Inertia::render('Tutor/Group/Index', [
            'group' => Group::find($id),
        ]);
    }

    public function join(Request $request, $id)
    {
        // get tutor
        $tutor = Tutor::find($request->session()->get('tutor'));
        $tutor->group_id = $id;
        $tutor->save();

        // redirect to group page
        return Redirect::to('/tutor/group/' . $id);
    }

    public function create()
    {
        return Inertia::render('Tutor/Group/Create', []);
    }

    public function finish()
    {
        return Inertia::render('Tutor/Group/Finish', []);
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
