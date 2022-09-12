<?php

namespace Tests\Unit;

use App\Models\GroupTutor;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

/**
 * zum Ausfüren der Tests: php artisan test
 *
 */
class GroupTutorDBEntryTest extends TestCase{
	use RefreshDatabase;

	/**
	 * test if a GroupTutor was created
	 *
	 * @return void
	 */
	public function test_group_t_creation(){
		$group_t = $this->insert_single_group_t();
		$this->assertDatabaseCount('group_tutor',1);
		$this->assertDatabaseModelExists($group_t);
	}

	public function insert_single_group_t(){
		return GroupTutor::factory()->create();
	}
}

