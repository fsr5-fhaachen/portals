<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Articles with bookings can not be deleted (restrict), so the bookings needed for
     * the billing can not get lost accidentally.
     */
    public function up(): void
    {
        Schema::create('article_action_logs', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
            $table->timestamps();
            $table->foreignId('user_id')->nullable(false)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('article_id')->nullable(false)->constrained()->onUpdate('cascade')->onDelete('restrict');
            $table->string('ip', 45)->nullable();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_action_logs');
    }
};
