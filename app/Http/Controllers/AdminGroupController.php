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


    private $courseNames = [
        'ET',
        'INF',
        'MCD',
        'WI'
    ];


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


    public function calculateMinAmountGroups($groupSize)
    {
        $totalStudents = Student::query()->count();
        return ceil($totalStudents / $groupSize);
    }

    public function calculateCoursePercentages()
    {
        $totalStudents = Student::query()->count();
        $coursePercentages = [];
        foreach ($this->courseNames as $course){
            $coursePercentages[$course] = ( Student::query()
                ->where('student_course', 'LIKE', $course)
                ->count() ) / $totalStudents;
        }
        return $coursePercentages;
    }

    public function calculateCourseDistribution($groupSize = 20)
    {
        $coursePercentages = $this->calculateCoursePercentages();
        $courseDistribution = [];
        foreach ($this->courseNames as $course){
            $courseDistribution[$course] = floor($groupSize * $coursePercentages[$course]);
        }
        return $courseDistribution;
    }

    public function randAssignCourse($course, $amountGroups, $coursePerGroup)
    {
        $studentsOfCourse = Student::getByCourse($course)->shuffle();
        for ($groupId = 1; $groupId <= $amountGroups; $groupId++){
            $chunk = $studentsOfCourse->forPage($groupId, $coursePerGroup);
            foreach ($chunk as $student){
                $student->group_id = $groupId;
                $student->save();
            }
        }
        return $studentsOfCourse->where('group_id', '=', null);
    }

    public function handleUnassignedStudents($unassignedStudents, $amountGroups, $groupSize)
    {
        $groupId = 1;
        foreach ($unassignedStudents as $student){
            if ($groupId > $amountGroups) $groupId = 1;
            $student->group_id = $groupId;
            $student->save();
            $groupId++;
        }
        return $groupId;
    }

    public function randAssignmentGroupPhase($groupSize)
    {
        // TODO TEST the random assignment of students for regular Group Phases which uses calculated distribution of courses
        $courseDistribution = $this->calculateCourseDistribution($groupSize);
        $amountGroups = $this->calculateMinAmountGroups($groupSize);

        $unassignedStudents = [];
        foreach ($this->courseNames as $course){
            $unassignedStudents[$course] = $this->randAssignCourse($course, $amountGroups, $courseDistribution[$course]);
        }

        $unassignedStudents = collect($unassignedStudents)->collapse()->shuffle();
        return $this->handleUnassignedStudents($unassignedStudents, $amountGroups, $groupSize);
    }

    public function randAssignmentFhTour($groupSize, $course){
        // TODO implement the random assignment of students for the FH Tour which takes course and timeslots into account
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
