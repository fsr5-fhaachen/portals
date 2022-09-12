<?php

namespace Tests\Unit;

use App\Models\Group;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

/**
 * zum Ausfüren der Tests: php artisan test
 *
 */
class GroupDBEntryTest extends TestCase{
	use RefreshDatabase;

	/**
	 * test if a group was created
	 *
	 * @return void
	 */
	public function test_group_creation(){
		$group = $this->insert_single_group();
		$this->assertDatabaseCount('groups',1);
		$this->assertDatabaseModelExists($group);
	}

	public function insert_single_group(){
		return Group::factory()->create();
	}
}

