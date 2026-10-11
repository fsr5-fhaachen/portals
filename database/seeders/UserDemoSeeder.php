<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // create one super admin user
        $superAdminUser = User::factory()->create([
            'firstname' => 'Super',
            'lastname' => 'Admin',
            'email' => 'superadmin@example.com',
            'pin' => '1234',
        ]);
        $superAdminUser->assignRole(['super admin', 'admin', 'tutor', 'special']);

        // create one admin user
        $adminUser = User::factory()->create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'email' => 'admin@example.com',
            'pin' => '1234',
        ]);
        $adminUser->assignRole(['admin', 'tutor']);

        // create one esa user
        $esaUser = User::factory()->create([
            'firstname' => 'ESA',
            'lastname' => 'User',
            'email' => 'esa@exampe.com',
            'pin' => '1234',
        ]);
        $esaUser->assignRole(['esa', 'tutor']);

        // create one stage tutor user
        $stageTutorUser = User::factory()->create([
            'firstname' => 'Stage',
            'lastname' => 'Tutor',
            'email' => 'stagetutor@example.com',
            'pin' => '1234',
        ]);
        $stageTutorUser->assignRole(['stage tutor', 'tutor']);

        // create one tutor user
        $tutorUser = User::factory()->create([
            'firstname' => 'Tutor',
            'lastname' => 'User',
            'email' => 'tutor@example.com',
            'pin' => '1234',
        ]);
        $tutorUser->assignRole(['tutor']);

        // create 800 student users
        User::factory()->count(800)->create();
    }
}
