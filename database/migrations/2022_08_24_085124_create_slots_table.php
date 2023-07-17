<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('slots', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->nullable(false);
            $table->timestamps();
            $table->foreignId('event_id')->nullable(false)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->boolean('has_requirements')->default(false);
            $table->integer('maximum_participants')->nullable();
            $table->json('form')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('slots');
    }
};
