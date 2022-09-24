<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $importPath = base_path('imported_images.csv');
        $csvFile = fopen($importPath, 'r');

        while (($data = fgetcsv($csvFile, 2000, ';')) !== False){
            if (empty($data[0])) continue;

            Student::create([
                'firstname' => $data[0],
                'lastname' => $data[1],
                'email' => $data[0] . '.' . $data[1] . '@alumni.fh-aachen.de',
                'course' => $data[2],
                'group_id' => null,
                'timeslot_id' => null,
                'attended' => 0
            ]);
        }

        fclose($csvFile);
    }
}
