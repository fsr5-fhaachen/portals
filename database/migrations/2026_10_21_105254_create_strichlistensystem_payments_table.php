<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Payments of a user for the strichlistensystem (amount in cents). An undone payment is soft deleted.
     */
    public function up(): void
    {
        Schema::create('strichlistensystem_payments', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
            $table->timestamps();
            $table->foreignId('user_id')->nullable(false)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->onUpdate('cascade')->nullOnDelete();
            $table->unsignedInteger('amount')->nullable(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('strichlistensystem_payments');
    }
};
