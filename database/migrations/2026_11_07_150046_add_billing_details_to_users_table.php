<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The address of a user that wants to pay the strichlistensystem by bank transfer with an invoice.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('wants_bank_transfer')->default(false);
            $table->string('billing_name')->nullable();
            $table->string('billing_street')->nullable();
            $table->string('billing_postal_code', 20)->nullable();
            $table->string('billing_city')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['wants_bank_transfer', 'billing_name', 'billing_street', 'billing_postal_code', 'billing_city']);
        });
    }
};
