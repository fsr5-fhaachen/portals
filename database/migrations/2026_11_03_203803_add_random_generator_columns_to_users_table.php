<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('random_generator_opt_out')->default(false)->after('is_disabled');
            $table->timestamp('random_generator_drawn_at')->nullable()->after('random_generator_opt_out');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['random_generator_opt_out', 'random_generator_drawn_at']);
        });
    }
};
