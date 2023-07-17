<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stops', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('group_id')->nullable(false)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('station_id')->nullable(false)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->timestamp('arrival_at')->nullable(true);
            $table->timestamp('departure_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stops');
    }
};
