<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Course::create([
            'name' => 'B. Eng. Elektrotechnik',
            'abbreviation' => 'ET'
        ]);
        Course::create([
            'name' => 'B. Sc. Informatik',
            'abbreviation' => 'INF'
        ]);
        Course::create([
            'name' => 'B. Sc. Media and Communications for Digital Business',
            'abbreviation' => 'MCD'
        ]);
        Course::create([
            'name' => 'B. Sc. Wirtschaftsinformatik',
            'abbreviation' => 'WI'
        ]);
        Course::create([
            'name' => 'B. Eng. Smart Building Engineering',
            'abbreviation' => 'SBE'
        ]);
        Course::create([
            'name' => 'M. Eng. Elektrotechnik',
            'abbreviation' => 'ET-Master'
        ]);
        Course::create([
            'name' => 'M. Eng. Information Systems Engineering',
            'abbreviation' => 'ISE-Master'
        ]);
    }
}
