<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('task_completions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('task_id')->nullable(false)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('group_id')->nullable(false)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('set null');

            $table->unique(['task_id', 'group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_completions');
    }
};
