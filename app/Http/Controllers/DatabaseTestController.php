<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\Tutor;
use App\Models\Group;
use App\Models\Timeslot;
use App\Models\Station;

class DatabaseTestController extends Controller
{

    // needs to be changed if database changes!
    private $tableNames = [
        'grouphasstation',
        'groups',
        'stations',
        'students',
        'timeslots',
        'tutors'
    ];

    public function clearAllTables()
    {
        foreach ($this->tableNames as $tableName){
            DB::table($tableName)->delete();
        }
    }

    public function clearTable($tableName)
    {
        DB::table($tableName)->delete();
    }

    // Randomly fills specified table by given amount by using the factories
    public function randomFillTable($tableName, $amount)
    {
        if ($tableName === 'students') return $this->randomFillStudents($amount);
        elseif ($tableName === 'tutors') return $this->randomFillTutors($amount);
        elseif ($tableName === 'groups') return $this->randomFillGroups($amount);
        elseif ($tableName === 'timeslots') return $this->randomFillTimeslots($amount);
        elseif ($tableName === 'stations') return $this->randomFillStations($amount);
        else return 'Table not found!';
    }

    public function randomFillStudents($amount)
    {
        $student = Student::factory()->count($amount)->create();
        return $student;
    }
    public function randomFillTutors($amount)
    {
        $tutor = Tutor::factory()->count($amount)->create();
        return $tutor;
    }
    public function randomFillGroups($amount)
    {
        $group = Group::factory()->count($amount)->create();
        return $group;
    }
    public function randomFillTimeslots($amount)
    {
        $timeslot = Timeslot::factory()->count($amount)->create();
        return $timeslot;
    }
    public function randomFillStations($amount)
    {
        $station = Station::factory()->count($amount)->create();
        return $station;
    }

    public function simulatedFillStudents($et, $inf, $mcd, $wi)
    {
        Student::factory()->state(['student_course' => 'ET'])->count($et)->create();
        Student::factory()->state(['student_course' => 'INF'])->count($inf)->create();
        Student::factory()->state(['student_course' => 'MCD'])->count($mcd)->create();
        Student::factory()->state(['student_course' => 'WI'])->count($wi)->create();
        return Student::all();
    }

    // Returns all students where specified attribute matches provided value
    public function getStudentsBy($attr, $val = '')
    {
        if ($attr === 'course') return $this->getStudentsByCourse($val);
        elseif ($attr === 'attended') return $this->getStudentsByAttendance($val);
        else return $attr+' is not a supported attribute';
    }
    public function getStudentsByCourse($course = '')
    {
        $student = Student::getByCourse($course);
        return $student;
    }
    public function getStudentsByAttendance($attendance = '')
    {
        $student = Student::getByAttendance($attendance);
        return $student;
    }

    // Returns all tutors where specified attribute matches provided value
    public function getTutorsBy($attr, $val = '')
    {
        if ($attr === 'course') return $this->getTutorsByCourse($val);
        elseif ($attr === 'available') return $this->getTutorsByAvailability($val);
        else return $attr+' is not a supported attribute';
    }
    public function getTutorsByCourse($course = '')
    {
        $tutor = Tutor::getByCourse($course);
        return $tutor;
    }
    public function getTutorsByAvailability($availability = '')
    {
        $tutor = Tutor::getByAvailability($availability);
        return $tutor;
    }
}
