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
        Schema::create('group_tutors', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('users_id')->nullable(False)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('groups_id')->nullable(False)->constrained()->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_tutors');
    }
};
