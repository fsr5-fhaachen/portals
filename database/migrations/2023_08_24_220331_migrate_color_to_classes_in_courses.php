<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Map of color to classes.
     *
     * @var array<string, string>
     */
    protected $colorToClasses = [
        'rgb(234 179 8)' => 'bg-yellow-500 text-white dark:bg-yellow-600',
        'rgb(29 78 216)' => 'bg-blue-700 text-white dark:bg-blue-800',
        'rgb(91 33 182)' => 'bg-violet-800 text-white dark:bg-violet-900',
        'rgb(22 101 52)' => 'bg-green-800 text-white dark:bg-green-900',
        'rgb(41 37 36)' => 'bg-stone-800 text-white dark:bg-stone-900',
        'rgb(239 68 68)' => 'bg-red-500 text-white dark:bg-red-600',
        'rgb(14 116 144)' => 'bg-cyan-700 text-white dark:bg-cyan-800',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // first create the new column
        Schema::table('courses', function (Blueprint $table) {
            $table->string('classes', 255);
        });

        // try to migrate the colors to classes
        foreach ($this->colorToClasses as $color => $classes) {
            DB::table('courses')
                ->where('color', $color)
                ->update(['classes' => $classes]);
        }

        // drop the old column
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // first create the old column
        Schema::table('courses', function (Blueprint $table) {
            $table->string('color', 64)->nullable(false);
        });

        // try to migrate the classes to colors
        foreach ($this->colorToClasses as $color => $classes) {
            DB::table('courses')
                ->where('classes', $classes)
                ->update(['color' => $color]);
        }

        // drop the new column
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('classes');
        });
    }
};
