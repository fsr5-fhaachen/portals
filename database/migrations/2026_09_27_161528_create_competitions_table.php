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
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name', 255);
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('points_win')->default(4);
            $table->unsignedSmallInteger('points_draw')->default(2);
            $table->unsignedSmallInteger('points_loss')->default(0);
            $table->unsignedSmallInteger('bonus_pool')->default(2);
            $table->boolean('bonus_per_team')->default(true);
            $table->boolean('is_open')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
