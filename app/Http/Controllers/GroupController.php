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
        // TODO TEST creation of student model from given data and persisting it in the database
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
        // TODO TEST implement setting the 'timeslot_id' attribute of specified student to provided timeslot
        Student::query()->find($studentId)->update(['timeslot_id' => $timeslotId]);
    }
}
