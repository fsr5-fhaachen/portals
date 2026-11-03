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
        Schema::create('duels', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('competition_id')->constrained()->restrictOnDelete();
            $table->foreignId('team_a_id')->constrained('competition_teams')->restrictOnDelete();
            $table->foreignId('team_b_id')->constrained('competition_teams')->restrictOnDelete();
            $table->string('result', 16);
            $table->unsignedSmallInteger('bonus_a')->default(0);
            $table->unsignedSmallInteger('bonus_b')->default(0);
            $table->string('note', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['competition_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('duels');
    }
};
