<?php

namespace Tests\Unit;

use App\Models\Stop;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class StopDBEntryTest extends TestCase{
	use RefreshDatabase;

	/**
	 * test if a stop was created
	 *
	 * @return void
	 */
	public function test_stop_creation(){
		$stop = $this->insert_single_stop();
		$this->assertDatabaseCount('stops',1);
		$this->assertDatabaseModelExists($stop);
	}

	public function insert_single_stop(){
		return Stop::factory()->create();
	}
}
