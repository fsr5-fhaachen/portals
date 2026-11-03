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
        Schema::create('extra_points', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('competition_id')->constrained()->restrictOnDelete();
            $table->foreignId('competition_team_id')->constrained()->restrictOnDelete();
            $table->smallInteger('points');
            $table->string('reason', 255);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extra_points');
    }
};
