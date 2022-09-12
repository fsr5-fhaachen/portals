<?php

namespace Tests\Unit;

use App\Models\Page;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

/**
 * zum Ausfüren der Tests: php artisan test
 *
 */
class PageDBEntryTest extends TestCase{
	use RefreshDatabase;

	/**
	 * test if a page was created
	 *
	 * @return void
	 */
	public function test_page_creation(){
		$page = $this->insert_single_page();
		$this->assertDatabaseCount('pages',1);
		$this->assertDatabaseModelExists($page);
	}

	public function insert_single_page(){
		return Page::factory()->create();
	}
}

