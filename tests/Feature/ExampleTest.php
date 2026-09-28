<?php

namespace Tests\Feature;

use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_example(): void
    {
        $this->seed(ModuleSeeder::class);

        $response = $this->get('/');

        $response->assertStatus(302);
    }
}
