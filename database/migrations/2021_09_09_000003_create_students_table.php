<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('student_firstname',30)->nullable(False);
            $table->string('student_lastname',30)->nullable(False);
            $table->string('student_email',100)->nullable(False)->unique('students_mail_unique');
            $table->enum('student_course',['ET','INF','MCD','WI'])->nullable(False);
            $table->foreignId('group_id')->nullable()->constrained('groups')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('timeslot_id')->nullable()->constrained('timeslots')->onUpdate('cascade')->onDelete('cascade');
            $table->boolean('student_attended')->nullable(False);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('students');
    }
}
