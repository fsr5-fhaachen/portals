<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tablets that are allowed to open the kiosk. A tablet is paired once with the pairing url of the device,
     * which stores the token in a cookie. The token is stored encrypted, so the pairing url can be shown again.
     */
    public function up(): void
    {
        Schema::create('strichlistensystem_kiosk_devices', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name', 50)->nullable(false);
            $table->text('token')->nullable(false);
            $table->timestamp('last_used_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('strichlistensystem_kiosk_devices');
    }
};
