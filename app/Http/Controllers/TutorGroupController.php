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

    public function setTutorAvailability($studentId, $value)
    {
        // TODO implement setting the 'tutor_available' attribute of specified tutor to given value
    }

    public function suggestGroupSize($course = '')
    {
        // TODO implement suggestion of a max group size by taking the course (if provided) and the amount of students and tutors into account
    }

    public function randAssignStudents($groupSize, $byCourse = False)
    {
        // TODO implement random assignment of students with respect to provided max group size and course if provided
    }

    public function randAssignTutors($groupSize, $byCourse = False)
    {
        // TODO implement random assignment of tutors with respect to provided max group size and course if provided
    }

    public function manualAssignStudent($studentId, $groupId)
    {
        // TODO implement assigning specified student to certain group
    }

    public function manualAssignTutor($tutorId, $groupId)
    {
        // TODO implement assigning specified tutor to certain group
    }
}
