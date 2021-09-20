<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTutorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tutors', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('firstname', 30)->nullable(False);
            $table->string('lastname', 30)->nullable(False);
            $table->string('email', 100)->nullable(False)->unique('tutors_mail_unique');
            $table->enum('course', ['ET', 'INF', 'MCD', 'WI'])->nullable(False);
            $table->foreignId('group_id')->nullable()->constrained('groups')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('station_id')->nullable()->constrained('stations')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tutors');
    }
}
