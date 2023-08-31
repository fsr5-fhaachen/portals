<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = [
            [
                'name' => 'B. Eng. Elektrotechnik',
                'abbreviation' => 'ET',
                'icon' => 'fa-plug',
                'classes' => 'bg-yellow-500 text-white dark:bg-yellow-600',
                'show_on_registration' => true,
            ], [
                'name' => 'B. Sc. Informatik',
                'abbreviation' => 'INF',
                'icon' => 'fa-laptop-code',
                'classes' => 'bg-blue-700 text-white dark:bg-blue-800',
                'show_on_registration' => true,
            ], [
                'name' => 'B. Sc. Media and Communications for Digital Business',
                'abbreviation' => 'MCD',
                'icon' => 'fa-paint-brush',
                'classes' => 'bg-violet-800 text-white dark:bg-violet-900',
                'show_on_registration' => false,
            ], [
                'name' => 'B. Sc. Digital Innovation & Business',
                'abbreviation' => 'DIB',
                'icon' => 'fa-briefcase',
                'classes' => 'bg-lime-700 text-white dark:bg-lime-800',
                'show_on_registration' => true,
            ], [
                'name' => 'B. Sc. Wirtschaftsinformatik',
                'abbreviation' => 'WI',
                'icon' => 'fa-sack-dollar',
                'classes' => 'bg-green-800 text-white dark:bg-green-900',
                'show_on_registration' => true,
            ], [
                'name' => 'B. Eng. Smart Building Engineering',
                'abbreviation' => 'SBE',
                'icon' => 'fa-house-signal',
                'classes' => 'bg-stone-800 text-white dark:bg-stone-900',
                'show_on_registration' => false,
            ], [
                'name' => 'M. Eng. Elektrotechnik',
                'abbreviation' => 'ET-Master',
                'icon' => 'fa-person-through-window',
                'classes' => 'bg-red-500 text-white dark:bg-red-600',
                'show_on_registration' => true,
            ], [
                'name' => 'M. Eng. Information Systems Engineering',
                'abbreviation' => 'ISE-Master',
                'icon' => 'fa-bug',
                'classes' => 'bg-cyan-700 text-white dark:bg-cyan-800',
                'show_on_registration' => true,
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
            $newCourse->classes = $course['classes'];
            $newCourse->show_on_registration = $course['show_on_registration'];

            // save the course
            $newCourse->save();
        }
    }
}
