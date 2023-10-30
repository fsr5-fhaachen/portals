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
        // create one admin user
        User::factory()->elevatedPrivileges(true, true)->create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'email' => 'admin@example.com',
        ]);

        // create one tutor user
        User::factory()->elevatedPrivileges(true, false)->create([
            'firstname' => 'Tutor',
            'lastname' => 'User',
            'email' => 'tutor@example.com',
        ]);

        // create 800 student users
        User::factory()->count(800)->create();
    }
}
