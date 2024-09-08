<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // first create the new table
        Schema::create('course_group', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('course_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('group_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });

        // second, migrate current data to new table
        DB::table('groups')->whereNotNull('course_id')->orderBy('id')->each(function ($group) {
            DB::table('course_group')->insert([
                'group_id' => $group->id,
                'course_id' => $group->course_id,
                'created_at' => $group->created_at,
                'updated_at' => $group->updated_at,
            ]);
        });

        // third, remove the old column
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('course_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // restore old column
        Schema::table('groups', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable(false)->constrained()->onUpdate('cascade')->onDelete('cascade');
        });

        // migrate data back
        DB::table('course_group')->each(function ($groupCourse) {
            DB::table('groups')->where('id', $groupCourse->group_id)->update([
                'course_id' => $groupCourse->course_id,
            ]);
        });

        // delete new table
        Schema::dropIfExists('course_group');
    }
};
