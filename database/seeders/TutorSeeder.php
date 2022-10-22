<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TutorSeeder extends Seeder
{
    /**
     * Set path to the file with tutors data.
     *
     * @var string
     */
    private const TUTORS_CSV_PATH = __DIR__ . '/tutors.csv';

    /**
     * Run the tutor seeds.
     *
     * @return void
     */
    public function run()
    {
        // check if the file exists
        if (!file_exists(self::TUTORS_CSV_PATH)) {
            return;
        }

        // get course
        $course = Course::all();

        // map course by abbreviation
        $courseByKey = $course->mapWithKeys(function ($item) {
            return [$item->abbreviation => $item];
        });

        // read the tutors.csv file
        $tutors = array_map('str_getcsv', file(self::TUTORS_CSV_PATH));

        // remove the header row
        array_shift($tutors);

        // loop through the tutors
        foreach ($tutors as $tutorRaw) {
            // get the tutor
            $tutor = explode(';', $tutorRaw[0]);

            // check if tutor exists
            $user = User::where('email', $tutor[3])->first();
            if ($user) {
                continue;
            }

            // create a new user
            $user = new User();
            $user->lastname = $tutor[0];
            $user->firstname = $tutor[1];
            $user->course_id = $courseByKey[$tutor[2]]->id;
            $user->email = $tutor[3];
            $user->is_tutor = true;

            // check if the user is an admin
            if ($tutor[4] == '1') {
                $user->is_admin = true;
            }

            // save the user
            $user->save();
        }
    }
}
