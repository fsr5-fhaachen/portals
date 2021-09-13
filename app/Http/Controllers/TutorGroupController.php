<?php

namespace App\Http\Controllers;

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
        // TODO implement setting the 'student_attended' attribute of specified student to given value
    }

    public function setGroupStation($groupId, $stationId)
    {
        // TODO implement setting the 'station_id' attribute of specified group to given id
    }
}
