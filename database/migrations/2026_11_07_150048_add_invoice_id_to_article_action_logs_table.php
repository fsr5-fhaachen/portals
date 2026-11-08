<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The invoice of a booking. A booking is only on one invoice, later bookings get on the next invoice.
     */
    public function up(): void
    {
        Schema::table('article_action_logs', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->constrained('strichlistensystem_invoices')->onUpdate('cascade')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('article_action_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_id');
        });
    }
};
