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
        Schema::table('stops', function (Blueprint $table) {
            $table->unsignedTinyInteger('round');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedTinyInteger('points')->nullable();
            $table->unsignedTinyInteger('bonus')->nullable()->default(0);

            $table->unique(['round', 'group_id']);
            $table->index(['station_id', 'round']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stops', function (Blueprint $table) {
            $table->dropUnique(['round', 'group_id']);
            $table->dropIndex(['station_id', 'round']);
            $table->dropColumn(['round', 'starts_at', 'ends_at', 'points', 'bonus']);
        });
    }
};
