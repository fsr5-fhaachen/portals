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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('firstname', 32)->nullable(False);
            $table->string('lastname', 32)->nullable(False);
            $table->string('email', 255)->nullable(False);
            $table->foreignId('course_id')->nullable(False)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->boolean('is_tutor')->default(False);
            $table->boolean('is_admin')->default(False);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
