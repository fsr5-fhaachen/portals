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
        Schema::create('workshop_assignments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('registration_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('workshop_session_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            // copy of the block of the session, so that a user can only have one workshop per block
            $table->foreignId('workshop_block_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            $table->unique(['registration_id', 'workshop_block_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshop_assignments');
    }
};
