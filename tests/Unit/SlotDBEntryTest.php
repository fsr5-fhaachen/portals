<?php

namespace Tests\Unit;

use App\Models\Slot;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

/**
 * zum Ausfüren der Tests: php artisan test
 *
 */
class SlotDBEntryTest extends TestCase{
	use RefreshDatabase;

	/**
	 * test if a slot was created
	 *
	 * @return void
	 */
	public function test_slot_creation(){
		$slot = $this->insert_single_slot();
		$this->assertDatabaseCount('slots',1);
		$this->assertDatabaseModelExists($slot);
	}

	public function insert_single_slot(){
		return Slot::factory()->create();
	}
}

