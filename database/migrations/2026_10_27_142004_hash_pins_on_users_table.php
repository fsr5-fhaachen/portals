<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The pin was stored in plain text. It is now stored as hash, so the column needs space for the hash.
     * Existing pins are hashed, so the users can keep their pin.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pin')->nullable()->change();
        });

        DB::table('users')->whereNotNull('pin')->orderBy('id')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                if (Hash::isHashed($user->pin)) {
                    continue;
                }

                DB::table('users')->where('id', $user->id)->update([
                    'pin' => Hash::make($user->pin),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * The hashed pins can not be restored, so all users have to set their pin again.
     */
    public function down(): void
    {
        DB::table('users')->update(['pin' => null]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('pin', 4)->nullable()->change();
        });
    }
};
