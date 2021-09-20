<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupHasStationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groupHasStation', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('group_id')->nullable(False)->constrained('groups')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('station_id')->nullable(False)->constrained('stations')->onUpdate('cascade')->onDelete('cascade');
            $table->unique(['group_id', 'station_id']);
            $table->integer('step', False, True)->nullable(False);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('groupHasStation');
    }
}
