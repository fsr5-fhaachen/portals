<?php

namespace App\Http\Controllers;

use App\Models\Student;
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

    public function updateOrCreateStudent($firstName, $lastName, $email, $course)
    {
        Student::query()->updateOrCreate(
            ['student_email' => $email],
            [
                'student_firstname' => $firstName,
                'student_lastname' => $lastName,
                'student_course' => $course,
                'group_id' => null,
                'timeslot_id' => null,
                'student_attended' => False
            ]);
    }

    public function setStudentTimeslotPreference($studentId, $timeslotId)
    {
        Student::query()->find($studentId)->update(['timeslot_id' => $timeslotId]);
    }
}
