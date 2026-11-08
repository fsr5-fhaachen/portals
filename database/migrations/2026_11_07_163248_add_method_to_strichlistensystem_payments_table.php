<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The payment method (cash or bank transfer), the existing payments were paid in cash.
     */
    public function up(): void
    {
        Schema::table('strichlistensystem_payments', function (Blueprint $table) {
            $table->string('method', 20)->default('cash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('strichlistensystem_payments', function (Blueprint $table) {
            $table->dropColumn('method');
        });
    }
};
