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

    /**
     * Creates a Student model from provided data and persists it in the database.
     * If the database contains an entry with the same email, the entry gets updated instead.
     *
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $course
     */
    public function updateOrCreateStudent($firstName, $lastName, $email, $course)
    {
        Student::query()->updateOrCreate(
            ['email' => $email],
            [
                'firstname' => $firstName,
                'lastname' => $lastName,
                'course' => $course,
                'id' => null,
                'id' => null,
                'attended' => False
            ]
        );
    }

    /**
     * Gets a Student model from the database by its ID and updates the id.
     *
     * @param mixed $studentId
     * @param mixed $timeslotId
     */
    public function setStudentTimeslotPreference($studentId, $timeslotId)
    {
        Student::query()->find($studentId)->update(['id' => $timeslotId]);
    }
}
