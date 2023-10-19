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
            'email' => 'superadmin@example.com'
        ]);
        $superAdminUser->assignRole(['super admin', 'admin', 'tutor']);

        // create one admin user
        $adminUser = User::factory()->create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'email' => 'admin@example.com',
        ]);
        $adminUser->assignRole(['admin', 'tutor']);

        // create one esa user
        $esaUser = User::factory()->create([
            'firstname' => 'ESA',
            'lastname' => 'User',
            'email' => 'esa@exampe.com',
        ]);
        $esaUser->assignRole(['esa', 'tutor']);

        // create one stage tutor user
        $stageTutorUser = User::factory()->create([
            'firstname' => 'Stage',
            'lastname' => 'Tutor',
            'email' => 'stagetutor@example.com',
        ]);
        $stageTutorUser->assignRole(['stage tutor', 'tutor']);

        // create one tutor user
        $tutorUser = User::factory()->create([
            'firstname' => 'Tutor',
            'lastname' => 'User',
            'email' => 'tutor@example.com',
        ]);
        $tutorUser->assignRole(['tutor']);

        // create 800 student users
        User::factory()->count(800)->create();
    }
}
