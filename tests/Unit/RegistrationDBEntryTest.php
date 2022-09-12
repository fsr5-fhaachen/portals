<?php

namespace Tests\Unit;

use App\Models\Registration;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

/**
 * zum Ausfüren der Tests: php artisan test
 *
 */
class RegistrationDBEntryTest extends TestCase{
	use RefreshDatabase;

	/**
	 * test if a registration was created
	 *
	 * @return void
	 */
	public function test_registration_creation(){
		$registration = $this->insert_single_registration();
		$this->assertDatabaseCount('registrations',1);
		$this->assertDatabaseModelExists($registration);
	}

	public function insert_single_registration(){
		return Registration::factory()->create();
	}
}

