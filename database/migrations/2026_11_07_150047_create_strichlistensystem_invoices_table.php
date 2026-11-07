<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Invoices of the strichlistensystem. An invoice keeps everything that is printed on it (recipient, issuer and
     * items), so later changes of the settings or the address do not change an issued invoice. The amounts are in cents.
     * A cancelled invoice keeps its number, the number is never used again.
     */
    public function up(): void
    {
        Schema::create('strichlistensystem_invoices', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedInteger('sequence')->unique();
            $table->string('number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onUpdate('cascade')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->onUpdate('cascade')->nullOnDelete();
            $table->string('title');
            $table->json('recipient');
            $table->json('issuer');
            $table->json('items');
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('paid');
            $table->unsignedInteger('amount_due');
            $table->date('issued_on');
            $table->date('due_on');
            $table->timestamp('cancelled_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('strichlistensystem_invoices');
    }
};
