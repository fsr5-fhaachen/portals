<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseFacultySeeder extends Seeder
{
    /**
     * Run the course seeds with one course per faculty instead of the regular study programmes (e.g. for the
     * Kneipentour and Gerolstein, where the faculties FB1, FB4 and FB5 take part).
     */
    public function run(): void
    {
        $courses = [
            [
                'name' => 'FB1 Architektur',
                'abbreviation' => 'FB1',
                'icon' => 'fa-map',
                'classes' => 'bg-emerald-700 text-white dark:bg-emerald-800',
            ],
            [
                'name' => 'FB4 Gestaltung',
                'abbreviation' => 'FB4',
                'icon' => 'fa-paint-brush',
                'classes' => 'bg-violet-800 text-white dark:bg-violet-900',
            ],
            [
                'name' => 'FB5 Elektrotechnik und Informationstechnik',
                'abbreviation' => 'FB5',
                'icon' => 'fa-plug',
                'classes' => 'bg-yellow-500 text-white dark:bg-yellow-600',
            ],
        ];

        foreach ($courses as $course) {
            // check if course with abbreviation exists
            if (Course::where('abbreviation', $course['abbreviation'])->exists()) {
                continue;
            }

            // create a new course
            $newCourse = new Course;
            $newCourse->name = $course['name'];
            $newCourse->abbreviation = $course['abbreviation'];
            $newCourse->icon = $course['icon'];
            $newCourse->classes = $course['classes'];
            $newCourse->show_on_registration = true;

            // save the course
            $newCourse->save();
        }
    }
}
