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
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('firstname', 32)->nullable(false);
            $table->string('lastname', 32)->nullable(false);
            $table->string('email', 255)->nullable(false);
            $table->foreignId('course_id')->nullable(false)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->boolean('is_tutor')->default(false);
            $table->boolean('is_admin')->default(false);
            $table->rememberToken();
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
