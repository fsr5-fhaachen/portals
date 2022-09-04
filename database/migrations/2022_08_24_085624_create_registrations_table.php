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
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('event_id')->nullable(False)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_id')->nullable(False)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('slot_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('group_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->boolean('drinks_alcohol')->nullable();
            $table->boolean('fulfils_requirements')->nullable();
            $table->boolean('is_present')->default(False);
            $table->json('form_responses')->nullable();
            $table->integer('queue_position')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('registrations');
    }
};
