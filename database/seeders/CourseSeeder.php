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
            'abbreviation' => 'ET',
            'icon' => 'fa-plug',
            'color' => 'rgb(234 179 8)',
        ]);
        Course::create([
            'name' => 'B. Sc. Informatik',
            'abbreviation' => 'INF',
            'icon' => 'fa-laptop-code',
            'color' => 'rgb(29 78 216)',
        ]);
        Course::create([
            'name' => 'B. Sc. Media and Communications for Digital Business',
            'abbreviation' => 'MCD',
            'icon' => 'fa-paint-brush',
            'color' => 'rgb(91 33 182)',
        ]);
        Course::create([
            'name' => 'B. Sc. Wirtschaftsinformatik',
            'abbreviation' => 'WI',
            'icon' => 'fa-sack-dollar',
            'color' => 'rgb(22 101 52)',
        ]);
        Course::create([
            'name' => 'B. Eng. Smart Building Engineering',
            'abbreviation' => 'SBE',
            'icon' => 'fa-house-signal',
            'color' => 'rgb(41 37 36)',
        ]);
        Course::create([
            'name' => 'M. Eng. Elektrotechnik',
            'abbreviation' => 'ET-Master',
            'icon' => 'fa-person-through-window',
            'color' => 'rgb(239 68 68)',
        ]);
        Course::create([
            'name' => 'M. Eng. Information Systems Engineering',
            'abbreviation' => 'ISE-Master',
            'icon' => 'fa-bug',
            'color' => 'rgb(14 116 144)',
        ]);
    }
}
