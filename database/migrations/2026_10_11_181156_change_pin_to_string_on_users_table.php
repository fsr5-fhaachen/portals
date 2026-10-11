<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The pin was stored as integer, so leading zeros got lost (e.g. "0123" became 123).
     * The pin is now stored as string with exactly 4 digits. Existing pins are padded with
     * leading zeros, invalid pins (negative or more than 4 digits) are removed.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pin_string', 4)->nullable();
        });

        DB::table('users')->whereNotNull('pin')->orderBy('id')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $pin = (int) $user->pin;

                DB::table('users')->where('id', $user->id)->update([
                    'pin_string' => $pin >= 0 && $pin <= 9999 ? str_pad((string) $pin, 4, '0', STR_PAD_LEFT) : null,
                ]);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pin']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('pin_string', 'pin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('pin_integer')->nullable();
        });

        DB::table('users')->whereNotNull('pin')->orderBy('id')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                DB::table('users')->where('id', $user->id)->update([
                    'pin_integer' => (int) $user->pin,
                ]);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pin']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('pin_integer', 'pin');
        });
    }
};
