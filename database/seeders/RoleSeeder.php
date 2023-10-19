<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // create permissions if they don't exist
        $permissions = [];
        foreach ([
            'view statistics',
            'view hidden event details',
            'manage events',
            'manage users',
            'manage random generator',
        ] as $permission) {
            if (!Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
            }
            $permissions[$permission] = Permission::where('name', $permission)->first();
        }

        // create tutor role if it doesn't exist
        if (!Role::where('name', 'tutor')->exists()) {
            Role::create(['name' => 'tutor']);
        }
        $tutorRole = Role::where('name', 'tutor')->first();

        // create stage tutor role if it doesn't exist
        if (!Role::where('name', 'stage tutor')->exists()) {
            Role::create(['name' => 'stage tutor']);
        }
        $stageTutorRole = Role::where('name', 'stage tutor')->first();
        $stageTutorRole->givePermissionTo($permissions['manage random generator']);

        // create esa role if it doesn't exist
        if (!Role::where('name', 'esa')->exists()) {
            Role::create(['name' => 'esa']);
        }
        $esaRole = Role::where('name', 'esa')->first();
        $esaRole->givePermissionTo($permissions['view statistics']);
        $esaRole->givePermissionTo($permissions['view hidden event details']);

        // create admin role if it doesn't exist
        if (!Role::where('name', 'admin')->exists()) {
            Role::create(['name' => 'admin']);
        }
        $adminRole = Role::where('name', 'admin')->first();
        $adminRole->givePermissionTo($permissions['view statistics']);
        $adminRole->givePermissionTo($permissions['view hidden event details']);
        $adminRole->givePermissionTo($permissions['manage events']);
        $adminRole->givePermissionTo($permissions['manage random generator']);
        $adminRole->givePermissionTo($permissions['manage users']);

        // create super admin role if it doesn't exist
        if (!Role::where('name', 'super admin')->exists()) {
            Role::create(['name' => 'super admin']);
        }
        $superAdminRole = Role::where('name', 'super admin')->first();
        $superAdminRole->givePermissionTo($permissions['view statistics']);
        $superAdminRole->givePermissionTo($permissions['view hidden event details']);
        $superAdminRole->givePermissionTo($permissions['manage events']);
        $superAdminRole->givePermissionTo($permissions['manage random generator']);
        $superAdminRole->givePermissionTo($permissions['manage users']);
    }
}
