<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $courses = [
            [
                'name' => 'B. Eng. Elektrotechnik',
                'abbreviation' => 'ET',
                'icon' => 'fa-plug',
                'color' => 'rgb(234 179 8)',
            ], [
                'name' => 'B. Sc. Informatik',
                'abbreviation' => 'INF',
                'icon' => 'fa-laptop-code',
                'color' => 'rgb(29 78 216)',
            ], [
                'name' => 'B. Sc. Media and Communications for Digital Business',
                'abbreviation' => 'MCD',
                'icon' => 'fa-paint-brush',
                'color' => 'rgb(91 33 182)',
            ], [
                'name' => 'B. Sc. Wirtschaftsinformatik',
                'abbreviation' => 'WI',
                'icon' => 'fa-sack-dollar',
                'color' => 'rgb(22 101 52)',
            ], [
                'name' => 'B. Eng. Smart Building Engineering',
                'abbreviation' => 'SBE',
                'icon' => 'fa-house-signal',
                'color' => 'rgb(41 37 36)',
            ], [
                'name' => 'M. Eng. Elektrotechnik',
                'abbreviation' => 'ET-Master',
                'icon' => 'fa-person-through-window',
                'color' => 'rgb(239 68 68)',
            ], [
                'name' => 'M. Eng. Information Systems Engineering',
                'abbreviation' => 'ISE-Master',
                'icon' => 'fa-bug',
                'color' => 'rgb(14 116 144)',
            ],
        ];

        foreach ($courses as $course) {
            // check if course with abbreviation exists
            $existingCourse = Course::where('abbreviation', $course['abbreviation'])->first();
            if ($existingCourse) {
                continue;
            }

            // create a new course
            $newCourse = new Course();
            $newCourse->name = $course['name'];
            $newCourse->abbreviation = $course['abbreviation'];
            $newCourse->icon = $course['icon'];
            $newCourse->color = $course['color'];

            // save the course
            $newCourse->save();
        }
    }
}
