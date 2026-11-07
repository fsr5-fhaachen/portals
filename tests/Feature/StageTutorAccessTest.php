<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StageTutorAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBasics();
        foreach (['randomGenerator', 'scoreSystem', 'countdown'] as $module) {
            $this->activateModule($module);
        }
    }

    /**
     * The pages of the stage functions.
     *
     * @return array<string, array{0: string}>
     */
    public static function stagePages(): array
    {
        return [
            'random generator' => ['dashboard.admin.randomGenerator.index'],
            'random generator display' => ['dashboard.admin.randomGenerator.display'],
            'score system' => ['dashboard.admin.scoreSystem.index'],
            'score system display' => ['dashboard.admin.scoreSystem.display'],
            'countdown' => ['dashboard.admin.countdown.index'],
            'countdown display' => ['dashboard.admin.countdown.display'],
        ];
    }

    #[DataProvider('stagePages')]
    public function test_stage_tutor_can_open_the_stage_pages(string $routeName): void
    {
        $this->actingAsTutor($this->createUserWithRoles('stage tutor'))
            ->get(route($routeName))
            ->assertOk();
    }

    #[DataProvider('stagePages')]
    public function test_admin_can_open_the_stage_pages(string $routeName): void
    {
        $this->actingAsTutor($this->createUserWithRoles('admin', 'tutor'))
            ->get(route($routeName))
            ->assertOk();
    }

    #[DataProvider('stagePages')]
    public function test_tutor_without_permission_is_forbidden(string $routeName): void
    {
        $this->actingAsTutor($this->createUserWithRoles('tutor'))
            ->get(route($routeName))
            ->assertForbidden();
    }

    #[DataProvider('stagePages')]
    public function test_stage_tutor_without_tutor_session_is_redirected_to_the_tutor_login(string $routeName): void
    {
        $this->actingAs($this->createUserWithRoles('stage tutor'))
            ->get(route($routeName))
            ->assertRedirect(route('dashboard.tutor.login'));
    }

    public function test_stage_tutor_can_save_the_score_system(): void
    {
        $this->actingAsTutor($this->createUserWithRoles('stage tutor'))
            ->postJson(route('dashboard.admin.scoreSystem.indexExecuteSubmit'), ['teams' => [['name' => 'Team 1', 'score' => '3']]])
            ->assertOk();
    }

    public function test_stage_tutor_can_not_open_other_admin_pages(): void
    {
        $this->actingAsTutor($this->createUserWithRoles('stage tutor'))
            ->get(route('dashboard.admin.users'))
            ->assertRedirect(route('dashboard.index'));
    }
}
