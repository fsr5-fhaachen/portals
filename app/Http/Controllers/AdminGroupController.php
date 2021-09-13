<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminGroupController extends Controller
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


    public function getAdminGroupInfo()
    {
        // TODO implement retrieving all groups and necessary information
    }


    public function createTutor($firstName, $lastName, $email, $course)
    {
        // TODO implement creation of tutor model from given data and persisting it in the database
    }

    public function createGroup($name)
    {
        // TODO implement creation of group model from given data and persisting it in the database
    }

    public function createStation($name)
    {
        // TODO implement creation of station model from given data and persisting it in the database
    }

    public function createTimeslot($name, $time)
    {
        // TODO implement creation of timeslot model from given data and persisting it in the database
    }

    public function createTourStep($groupId, $stationId, $step)
    {
        // TODO implement creation of groupHasStation model from given data and persisting it in the database
    }


    public function assignTimeslotToGroup($timeslotId, $groupId)
    {
        // TODO implement assigning specified timeslot to certain group
    }

    public function assignGroupToTutor($groupId, $tutorId)
    {
        // TODO implement assigning specified group to certain tutor
    }

    public function assignStationToTutor($stationId, $tutorId)
    {
        // TODO implement assigning specified station to certain tutor
    }


    public function randAssignStudents($groupSize, $byCourse = False, $byTimeslot = False)
    {
        // TODO implement random assignment of students with respect to provided max group size and course + timeslot preferences if provided
    }

    public function resetAttendance()
    {
        // TODO implement setting the 'student_attended' attribute of all students to False
    }

    public function resetAssignment()
    {
        // TODO implement setting the 'group_id' attribute of all students to null
    }
}
