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
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('event_id')->nullable(false)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_id')->nullable(false)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('slot_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('group_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->boolean('drinks_alcohol')->nullable();
            $table->boolean('fulfils_requirements')->nullable();
            $table->boolean('is_present')->default(false);
            $table->json('form_responses')->nullable();
            $table->integer('queue_position')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
