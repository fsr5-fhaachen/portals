<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name', 255)->nullable(False);
            $table->dateTime('registration_from');
            $table->dateTime('registration_until');
            $table->enum('type', ['group_phase', 'event_registration', 'slot_booking']);
            $table->boolean('has_requirements');
            $table->boolean('consider_alcohol');
            $table->json('form');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('events');
    }
};
