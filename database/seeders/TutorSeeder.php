<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class TutorSeeder extends Seeder
{
    /**
     * Set path to the file with tutors data.
     *
     * @var string
     */
    private const TUTORS_CSV_PATH = __DIR__.'/tutors.csv';

    /**
     * Run the tutor seeds.
     */
    public function run(): void
    {
        // check if the file exists
        if (! file_exists(self::TUTORS_CSV_PATH)) {
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
            echo 'Creating tutor '.$tutor[0].' '.$tutor[1].' ('.$tutor[3].')'.PHP_EOL;
            $user = new User;
            $user->lastname = $tutor[0];
            $user->firstname = $tutor[1];
            $user->course_id = $courseByKey[$tutor[2]]->id;
            $user->email = $tutor[3];

            //set user to disabled
            if (array_key_exists(5, $tutor) && $tutor[5] == '1') {
                echo 'Setting tutor to disabled for '.$tutor[0].' '.$tutor[1].' ('.$tutor[3].')'.PHP_EOL;
                $user->is_disabled = true;
            }

            // save the user
            $user->save();

            // assigne user role
            echo 'Assigning role tutor to '.$tutor[0].' '.$tutor[1].' ('.$tutor[3].')'.PHP_EOL;
            $user->assignRole('tutor');

            // check if the user has additional roles
            if (array_key_exists(4, $tutor)) {
                // split roles by comma
                $roles = explode('|', $tutor[4]);

                // loop through the roles
                foreach ($roles as $role) {
                    // skip empty strings because of ";;" in the csv file
                    if (empty($role)) {
                        continue;
                    }
                    echo 'Assigning role '.$role.' to '.$tutor[0].' '.$tutor[1].' ('.$tutor[3].')'.PHP_EOL;
                    $user->assignRole($role);
                }
            }
        }
    }
}
