<?php

use App\Models\User;
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
      User::chunk(100, function ($users) {
        foreach ($users as $user) {
          $user->email = strtolower($user->email);
          $user->save();
        }
      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
