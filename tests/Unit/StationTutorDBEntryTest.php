<?php

namespace Tests\Unit;

use App\Models\StationTutor;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class StationTutorDBEntryTest extends TestCase{
	use RefreshDatabase;

	/**
	 * test if a stationTutor was created
	 *
	 * @return void
	 */
	public function test_station_t_creation(){
		$station_t = $this->insert_single_station_t();
		$this->assertDatabaseCount('station_tutor',1);
		$this->assertDatabaseModelExists($station_t);
	}

	public function insert_single_station_t(){
		return StationTutor::factory()->create();
	}
}
