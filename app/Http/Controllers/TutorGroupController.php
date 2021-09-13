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


    public function setStudentAttendance($studentId, $value)
    {
        Student::find($studentId)->update(['student_attended' => $value]);
    }

    public function setGroupStation($groupId, $stationId)
    {
        Group::find($groupId)->update(['station_id' => $stationId]);
    }
}
