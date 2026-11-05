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
        Schema::create('workshop_sessions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('workshop_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('workshop_block_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('location', 255)->nullable();
            $table->unsignedInteger('minimum_participants')->default(0);
            $table->unsignedInteger('maximum_participants')->nullable();
            $table->boolean('is_cancelled')->default(false);

            $table->unique(['workshop_id', 'workshop_block_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshop_sessions');
    }
};
