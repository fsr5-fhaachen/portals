<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardAdminScoreSystemController;
use App\Models\Module;
use App\Models\State;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ScoreSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([RoleSeeder::class, ModuleSeeder::class, CourseSeeder::class]);

        Module::where('key', 'scoreSystem')->update(['active' => true, 'expose_public' => true]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(['admin', 'tutor']);
    }

    /**
     * Get teams with the given amount.
     *
     * @return list<array{name: string, score: string}>
     */
    private function teams(int $amount): array
    {
        return array_map(fn (int $index) => ['name' => "Team {$index}", 'score' => (string) $index], range(1, $amount));
    }

    /**
     * Save the teams of the score system as the admin that is logged in as tutor.
     *
     * @param  array<string, mixed>  $data
     */
    private function saveScoreSystem(array $data): TestResponse
    {
        return $this->actingAs($this->admin)->withSession(['tutor' => true])->postJson('/dashboard/admin/score-system', $data);
    }

    public function test_saves_any_amount_of_teams_up_to_the_maximum(): void
    {
        foreach ([1, 3, DashboardAdminScoreSystemController::MAXIMUM_TEAM_AMOUNT] as $amount) {
            $this->saveScoreSystem(['teams' => $this->teams($amount)])->assertOk();

            $this->assertSame($this->teams($amount), json_decode(State::where('key', 'scoreSystem')->value('value'), true)['teams']);
            $this->getJson('/public/api/score-system/state')->assertOk()->assertJsonCount($amount, 'teams');
        }
    }

    public function test_rejects_more_teams_than_the_maximum(): void
    {
        $this->saveScoreSystem(['teams' => $this->teams(DashboardAdminScoreSystemController::MAXIMUM_TEAM_AMOUNT + 1)])
            ->assertJsonValidationErrors('teams');

        $this->assertDatabaseMissing('states', ['key' => 'scoreSystem']);
    }

    public function test_rejects_no_teams(): void
    {
        $this->saveScoreSystem(['teams' => []])->assertJsonValidationErrors('teams');
        $this->saveScoreSystem([])->assertJsonValidationErrors('teams');

        $this->assertDatabaseMissing('states', ['key' => 'scoreSystem']);
    }

    public function test_rejects_teams_without_name_or_score(): void
    {
        $this->saveScoreSystem(['teams' => [['name' => '', 'score' => '1'], ['name' => 'Team 2']]])
            ->assertJsonValidationErrors(['teams.0.name', 'teams.1.score']);
    }
}
