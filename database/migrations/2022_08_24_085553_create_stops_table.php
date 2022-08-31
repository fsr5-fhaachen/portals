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
        Schema::create('stops', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('group_id')->nullable(False)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('station_id')->nullable(False)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->timestamp('arrival_at')->nullable(True);
            $table->timestamp('departure_at')->nullable(True);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stops');
    }
};