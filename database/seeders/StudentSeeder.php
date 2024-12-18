<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Set path to the file with students data.
     *
     * @var string
     */
    private const STUDENTS_CSV_PATH = __DIR__.'/students.csv';

    /**
     * Run the tutor seeds.
     */
    public function run(): void
    {
        // check if the file exists
        if (! file_exists(self::STUDENTS_CSV_PATH)) {
            return;
        }

        // get course
        $course = Course::all();

        // map course by abbreviation
        $courseByKey = $course->mapWithKeys(function ($item) {
            return [$item->abbreviation => $item];
        });

        // read the students.csv file
        $students = array_map('str_getcsv', file(self::STUDENTS_CSV_PATH));

        // remove the header row
        array_shift($students);

        // loop through the students
        foreach ($students as $studentRaw) {
            // get the student
            $student = explode(';', $studentRaw[0]);

            // check if student exists
            $user = User::where('email', strtolower($student[3]))->first();
            if ($user && ! $courseByKey[$student[2]]) {
                continue;
            }

            // create a new user
            $user = new User;
            $user->lastname = $student[0];
            $user->firstname = $student[1];
            $user->course_id = $courseByKey[$student[2]]->id;
            $user->email = strtolower($student[3]);

            // save the user
            $user->save();
        }
    }
}
