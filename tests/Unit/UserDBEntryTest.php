<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

/**
 * zum Ausfüren der Tests: php artisan test
 *
 */
class UserDBEntryTest extends TestCase{
 	use RefreshDatabase;

 	/**
	 * test if a user was created
	 *
	 * @return void
	 */
	 public function test_user_creation(){
		 $user = $this->insert_single_user();
		 $this->assertDatabaseCount('users',1);
		 $this->assertDatabaseModelExists($user);
	 }

	 public function insert_single_user(){
		 $user = User::factory()->create();
		 return $user;
	 }
}

