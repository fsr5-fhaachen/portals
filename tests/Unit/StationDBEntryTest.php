<?php

namespace Tests\Unit;

use App\Models\Station;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class StationDBEntryTest extends TestCase{
	use RefreshDatabase;

	/**
	 * test if a station was created
	 *
	 * @return void
	 */
	public function test_station_creation(){
		$station = $this->insert_single_station();
		$this->assertDatabaseCount('stations',1);
		$this->assertDatabaseModelExists($station);
	}

	public function insert_single_station(){
		return Station::factory()->create();
	}
}
