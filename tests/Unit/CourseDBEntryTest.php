<?php

namespace Tests\Unit;

use App\Models\Course;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

/**
 * zum Ausfüren der Tests: php artisan test
 *
 */
class CourseDBEntryTest extends TestCase{
	use RefreshDatabase;

	/**
	 * test if a course was created
	 *
	 * @return void
	 */
	public function test_course_creation(){
		$course = $this->insert_single_course();
		$this->assertDatabaseCount('courses',1);
		$this->assertDatabaseModelExists($course);
	}

	public function insert_single_course(){
		return Course::factory()->create();
	}
}

