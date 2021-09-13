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
        Group::query()->create([
            'group_name' => $name,
            'group_course' => null,
            'station_id' => null,
            'timeslot_id' => null
        ]);
    }

    public function createStation($name)
    {
        Station::query()->create([
            'station_name' => $name
        ]);
    }

    public function updateOrCreateTimeslot($name, $time)
    {
        Timeslot::query()->updateOrCreate(
            ['timeslot_name' => $name],
            [
            'timeslot_time' => $time
        ]);
    }

    public function createTourStep($groupId, $stationId, $step)
    {
        Grouphasstation::query()->create([
            'group_id' => $groupId,
            'station_id' => $stationId,
            'groupHasStation_step' => $step
        ]);
    }

    public function updateTourStep($groupHasStationId, $groupId, $stationId, $step)
    {
        Grouphasstation::query()->find($groupHasStationId)->update([
            'group_id' => $groupId,
            'station_id' => $stationId,
            'groupHasStation_step' => $step
        ]);
    }


    public function assignTimeslotToGroup($timeslotId, $groupId)
    {
        Group::query()->find($groupId)->update(['timeslot_id' => $timeslotId]);
    }

    public function assignGroupToTutor($groupId, $tutorId)
    {
        Tutor::query()->find($tutorId)->update(['group_id' => $groupId]);
    }

    public function assignStationToTutor($stationId, $tutorId)
    {
        Tutor::query()->find($tutorId)->update(['station_id' => $stationId]);
    }

    public function setGroupCourse($groupId, $course)
    {
        Group::query()->find($groupId)->update(['group_course' => $course]);
    }


    public function randAssignStudents($groupSize, $byCourse = False, $byTimeslot = False)
    {
        // TODO implement random assignment of students with respect to provided max group size and course + timeslot preferences if provided
    }

    public function resetStudentAttendance()
    {
        Student::query()->update(['student_attended' => False]);
    }

    public function resetGroupCourse()
    {
        Group::query()->update(['group_course' => null]);
    }

    public function resetGroupAssignment()
    {
        Student::query()->update(['group_id' => null]);
    }
}
