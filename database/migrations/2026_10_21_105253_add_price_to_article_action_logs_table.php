<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Every booking keeps the price (in cents) of the article at the time of the booking, so that later price
     * changes do not change existing bookings. Bookings without a price (null) were made before the article had
     * a price. They get the price once an admin sets the price of the article for the first time.
     */
    public function up(): void
    {
        Schema::table('article_action_logs', function (Blueprint $table) {
            $table->unsignedInteger('price')->nullable()->after('article_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('article_action_logs', function (Blueprint $table) {
            $table->dropColumn(['price']);
        });
    }
};
