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
        Schema::create('slots', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->nullable(False);
            $table->timestamps();
            $table->foreignId('event_id')->nullable(False)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->boolean('has_requirements');
            $table->integer('maximum_participants');
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
        Schema::dropIfExists('slots');
    }
};
