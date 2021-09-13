<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Grouphasstation;
use App\Models\Station;
use App\Models\Student;
use App\Models\Timeslot;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
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


    public function updateOrCreateTutor($firstName, $lastName, $email, $course)
    {
        // TODO TEST creation of tutor model from given data and persisting it in the database
        Tutor::query()->updateOrCreate(
            ['tutor_email' => $email],
            [
            'tutor_firstname' => $firstName,
            'tutor_lastname' => $lastName,
            'tutor_course' => $course,
            'group_id' => null,
            'station_id' => null
        ]);
    }

    public function createGroup($name)
    {
        // TODO TEST creation of group model from given data and persisting it in the database
        Group::query()->create([
            'group_name' => $name,
            'station_id' => null,
            'timeslot_id' => null
        ]);
    }

    public function createStation($name)
    {
        // TODO TEST creation of station model from given data and persisting it in the database
        Station::query()->create([
            'station_name' => $name
        ]);
    }

    public function updateOrCreateTimeslot($name, $time)
    {
        // TODO TEST creation of timeslot model from given data and persisting it in the database
        Timeslot::query()->updateOrCreate(
            ['timeslot_name' => $name],
            [
            'timeslot_time' => $time
        ]);
    }

    public function createTourStep($groupId, $stationId, $step)
    {
        // TODO TEST creation of groupHasStation model from given data and persisting it in the database
        Grouphasstation::query()->create([
            'group_id' => $groupId,
            'station_id' => $stationId,
            'groupHasStation_step' => $step
        ]);
    }

    public function updateTourStep($groupHasStationId, $groupId, $stationId, $step)
    {
        // TODO TEST update of groupHasStation model from given data and persisting it in the database
        Grouphasstation::query()->find($groupHasStationId)->update([
            'group_id' => $groupId,
            'station_id' => $stationId,
            'groupHasStation_step' => $step
        ]);
    }


    public function assignTimeslotToGroup($timeslotId, $groupId)
    {
        // TODO TEST assigning specified timeslot to certain group
        Group::query()->find($groupId)->update(['timeslot_id' => $timeslotId]);
    }

    public function assignGroupToTutor($groupId, $tutorId)
    {
        // TODO TEST assigning specified group to certain tutor
        Tutor::query()->find($tutorId)->update(['group_id' => $groupId]);
    }

    public function assignStationToTutor($stationId, $tutorId)
    {
        // TODO TEST assigning specified station to certain tutor
        Tutor::query()->find($tutorId)->update(['station_id' => $stationId]);
    }


    public function randAssignStudents($groupSize, $byCourse = False, $byTimeslot = False)
    {
        // TODO implement random assignment of students with respect to provided max group size and course + timeslot preferences if provided
    }

    public function resetAttendance()
    {
        // TODO TEST setting the 'student_attended' attribute of all students to False
        Student::query()->update(['student_attended' => False]);
    }

    public function resetAssignment()
    {
        // TODO TEST setting the 'group_id' attribute of all students to null
        Student::query()->update(['group_id' => null]);
    }
}
