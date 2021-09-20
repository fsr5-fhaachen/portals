<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TutorGroupController extends Controller
{
    public function index()
    {
        return Inertia::render('Tutor/Group/Index', []);
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
     * Gets a Student model from the database by its ID and updates the attendance.
     *
     * @param mixed $studentId
     * @param mixed $value The value that gets assigned to attended of specified student. Should be true or false.
     */
    public function setStudentAttendance($studentId, $value)
    {
        Student::find($studentId)->update(['attended' => $value]);
    }

    /**
     * Gets a Group model from the database by its ID and updates the id.
     *
     * @param mixed $groupId
     * @param mixed $stationId
     */
    public function setGroupStation($groupId, $stationId)
    {
        Group::find($groupId)->update(['id' => $stationId]);
    }
}
