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
            $table->string('tutor_firstname',30)->nullable(False);
            $table->string('tutor_lastname',30)->nullable(False);
            $table->string('tutor_email',100)->nullable(False)->unique('tutors_mail_unique');
            $table->enum('tutor_course',['ET','INF','MCD','WI'])->nullable(False);
            $table->foreignId('group_id')->nullable()->constrained('groups')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('station_id')->nullable()->constrained('stations')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('timeslot_id')->nullable()->constrained('timeslots')->onUpdate('cascade')->onDelete('cascade');
            $table->boolean('tutor_available')->nullable(False);
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
