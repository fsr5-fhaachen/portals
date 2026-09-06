<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $duplicates = DB::table('registrations')
            ->select('event_id', 'user_id')
            ->groupBy('event_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Refusing to add unique index: '.$duplicates->count().
                ' (event_id, user_id) pairs have duplicate registrations. '.
                'Reconcile them first, then re-run this migration.'
            );
        }

        Schema::table('registrations', function (Blueprint $table) {
            $table->unique(['event_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropUnique(['event_id', 'user_id']);
        });
    }
};
