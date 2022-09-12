<?php

namespace Tests\Unit;

use App\Models\Event;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

/**
 * zum Ausfüren der Tests: php artisan test
 *
 */
class EventDBEntryTest extends TestCase{
	use RefreshDatabase;

	/**
	 * test if an event was created
	 *
	 * @return void
	 */
	public function test_event_creation(){
		$event = $this->insert_single_event();
		$this->assertDatabaseCount('events',1);
		$this->assertDatabaseModelExists($event);
	}

	public function insert_single_event(){
		return Event::factory()->create();
	}
}

