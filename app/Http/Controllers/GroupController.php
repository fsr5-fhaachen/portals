<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class GroupController extends Controller
{
    public function index()
    {
        return Inertia::render('Home', []);
    }


    public function getStudentGroupInfo($studentId)
    {
        // TODO implement retrieving the group and maybe the necessary information for specified student
    }

    public function setStudentTimeslot($studentId, $timeslotId)
    {
        // TODO implement setting the 'timeslot_id' attribute of specified student to provided timeslot
    }
}
