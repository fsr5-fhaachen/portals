<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Existing installations are not always seeded again, so the module and the permission are created here.
     * The ActiveModule middleware fails without the module row and spatie denies a permission that does not exist.
     */
    public function up(): void
    {
        if (! DB::table('modules')->where('key', 'strichlistensystem')->exists()) {
            DB::table('modules')->insert([
                'key' => 'strichlistensystem',
                'active' => false,
                'expose_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permission = Permission::findOrCreate('manage strichlistensystem');

        // on a fresh installation the roles do not exist yet, the RoleSeeder assigns the permission then
        Role::whereIn('name', ['admin', 'super admin'])->get()->each(fn (Role $role) => $role->givePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('modules')->where('key', 'strichlistensystem')->delete();

        Permission::where('name', 'manage strichlistensystem')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
