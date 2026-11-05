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
        Schema::table('events', function (Blueprint $table) {
            // amount of workshops a user can choose by priority (workshop_booking events)
            $table->unsignedTinyInteger('workshop_priority_count')->default(3)->after('form');
            // the users only see their workshops after the assignment was published
            $table->timestamp('workshops_published_at')->nullable()->after('workshop_priority_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['workshop_priority_count', 'workshops_published_at']);
        });
    }
};
