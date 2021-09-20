<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('group_name',30)->nullable(False)->unique('groups_name_unique');
            $table->enum('group_course',['ET','INF','MCD','WI'])->nullable();
            $table->foreignId('station_id')->nullable()->constrained('stations')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('timeslot_id')->nullable()->constrained('timeslots')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('groups');
    }
}
