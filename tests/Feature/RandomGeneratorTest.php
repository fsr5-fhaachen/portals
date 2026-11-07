<?php

namespace Tests\Feature;

use App\Helpers\RandomGenerator;
use App\Models\Course;
use App\Models\Module;
use App\Models\State;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RandomGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Course $course;

    private Course $otherCourse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([RoleSeeder::class, ModuleSeeder::class, CourseSeeder::class]);

        Module::where('key', 'randomGenerator')->update(['active' => true]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(['admin', 'tutor']);

        [$this->course, $this->otherCourse] = Course::orderBy('id')->take(2)->get()->all();
    }

    /**
     * Act as the admin that is logged in as tutor.
     */
    private function actingAsAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession(['tutor' => true]);
    }

    /**
     * Store the settings of the random generator.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function saveSettings(array $overrides = []): void
    {
        app(RandomGenerator::class)->saveSettings(array_merge([
            'participants' => RandomGenerator::PARTICIPANTS_FIRST_YEARS,
            'course_ids' => [$this->course->id],
            'opt_out_enabled' => false,
            'tutor_opt_out_enabled' => false,
            'hide_opted_out' => false,
            'hide_other_courses' => false,
            'hide_drawn' => false,
        ], $overrides));
    }

    /**
     * Get the stored state of the random generator.
     *
     * @return array<string, mixed>
     */
    private function randomGeneratorState(): array
    {
        return json_decode(State::where('key', 'randomGenerator')->value('value'), true);
    }

    public function test_admin_page_is_not_reachable_if_the_module_is_inactive(): void
    {
        Module::where('key', 'randomGenerator')->update(['active' => false]);

        $this->actingAsAdmin()
            ->get(route('dashboard.admin.randomGenerator.index'))
            ->assertRedirect(route('app.index'));
    }

    public function test_user_without_admin_role_can_not_manage_the_random_generator(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('dashboard.admin.randomGenerator.resetDrawn'))
            ->assertRedirect(route('dashboard.index'));
    }

    public function test_admin_page_shows_participants_settings_and_pool_count(): void
    {
        $this->saveSettings();

        User::factory()->create(['course_id' => $this->course->id]);
        User::factory()->create(['course_id' => $this->course->id, 'random_generator_opt_out' => true]);
        User::factory()->create(['course_id' => $this->otherCourse->id]);

        $this->actingAsAdmin()
            ->get(route('dashboard.admin.randomGenerator.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Admin/RandomGenerator/Index')
                // the admin has a role and does not take part
                ->has('users', 3)
                ->where('poolCount', 1)
                ->where('settings.course_ids', [$this->course->id])
                ->where('state.state', 'setup')
            );
    }

    public function test_all_courses_are_selected_without_stored_settings(): void
    {
        $this->assertSame(Course::pluck('id')->all(), app(RandomGenerator::class)->settings()['course_ids']);
    }

    public function test_draw_only_picks_users_of_the_pool(): void
    {
        $this->saveSettings();

        $user = User::factory()->create(['course_id' => $this->course->id]);
        User::factory()->create(['course_id' => $this->course->id, 'random_generator_opt_out' => true]);
        User::factory()->create(['course_id' => $this->course->id, 'random_generator_drawn_at' => now()]);
        User::factory()->create(['course_id' => $this->otherCourse->id]);

        $this->actingAsAdmin()
            ->postJson(route('dashboard.admin.randomGenerator.indexExecuteSubmit'), ['state' => 'stopped'])
            ->assertOk()
            ->assertJson(['success' => true, 'user' => ['id' => $user->id]]);

        $this->assertNotNull($user->fresh()->random_generator_drawn_at);
        $this->assertSame('stopped', $this->randomGeneratorState()['state']);
        $this->assertSame($user->id, $this->randomGeneratorState()['user']['id']);
    }

    public function test_drawn_user_is_not_drawn_again(): void
    {
        $this->saveSettings();

        User::factory()->create(['course_id' => $this->course->id]);

        $this->actingAsAdmin()
            ->postJson(route('dashboard.admin.randomGenerator.indexExecuteSubmit'), ['state' => 'stopped'])
            ->assertJson(['success' => true]);

        $this->actingAsAdmin()
            ->postJson(route('dashboard.admin.randomGenerator.indexExecuteSubmit'), ['state' => 'stopped'])
            ->assertJson(['success' => false, 'message' => 'Es ist niemand mehr im Lostopf.']);
    }

    public function test_draw_with_empty_pool_does_not_change_the_state(): void
    {
        $this->saveSettings(['course_ids' => []]);

        User::factory()->create(['course_id' => $this->course->id]);

        $this->actingAsAdmin()
            ->postJson(route('dashboard.admin.randomGenerator.indexExecuteSubmit'), ['state' => 'running'])
            ->assertJson(['success' => true]);

        $this->actingAsAdmin()
            ->postJson(route('dashboard.admin.randomGenerator.indexExecuteSubmit'), ['state' => 'stopped'])
            ->assertJson(['success' => false]);

        $this->assertSame('running', $this->randomGeneratorState()['state']);
    }

    public function test_preselected_user_overrides_the_pool(): void
    {
        $this->saveSettings();

        User::factory()->create(['course_id' => $this->course->id]);
        $preselectedUser = User::factory()->create([
            'course_id' => $this->otherCourse->id,
            'random_generator_opt_out' => true,
            'random_generator_drawn_at' => now()->subHour(),
        ]);

        $this->actingAsAdmin()
            ->postJson(route('dashboard.admin.randomGenerator.indexExecuteSubmit'), ['state' => 'stopped', 'user_id' => $preselectedUser->id])
            ->assertJson(['success' => true, 'user' => ['id' => $preselectedUser->id]]);

        $this->assertTrue($preselectedUser->fresh()->random_generator_drawn_at->isAfter(now()->subMinute()));
    }

    public function test_running_state_contains_the_pool(): void
    {
        $this->saveSettings();

        $user = User::factory()->create(['course_id' => $this->course->id]);
        User::factory()->create(['course_id' => $this->otherCourse->id]);

        $this->actingAsAdmin()
            ->postJson(route('dashboard.admin.randomGenerator.indexExecuteSubmit'), ['state' => 'running'])
            ->assertJson(['success' => true]);

        $this->actingAsAdmin()
            ->getJson(route('api.randomGeneratorState'))
            ->assertOk()
            ->assertJson(['state' => 'running', 'userIds' => [$user->id]]);
    }

    public function test_settings_are_saved(): void
    {
        $this->actingAsAdmin()
            ->post(route('dashboard.admin.randomGenerator.updateSettings'), [
                'participants' => 'tutors',
                'course_ids' => [$this->otherCourse->id],
                'opt_out_enabled' => true,
                'tutor_opt_out_enabled' => true,
                'hide_opted_out' => true,
                'hide_other_courses' => false,
                'hide_drawn' => true,
            ])
            ->assertRedirect(route('dashboard.admin.randomGenerator.index'));

        $this->assertSame([
            'participants' => 'tutors',
            'course_ids' => [$this->otherCourse->id],
            'opt_out_enabled' => true,
            'tutor_opt_out_enabled' => true,
            'hide_opted_out' => true,
            'hide_other_courses' => false,
            'hide_drawn' => true,
        ], app(RandomGenerator::class)->settings());
    }

    public function test_settings_can_deselect_all_courses(): void
    {
        User::factory()->create(['course_id' => $this->course->id]);

        $this->actingAsAdmin()
            ->postJson(route('dashboard.admin.randomGenerator.updateSettings'), [
                'participants' => 'first_years',
                'course_ids' => [],
                'opt_out_enabled' => false,
                'tutor_opt_out_enabled' => false,
                'hide_opted_out' => false,
                'hide_other_courses' => false,
                'hide_drawn' => false,
            ])
            ->assertRedirect();

        $this->assertSame([], app(RandomGenerator::class)->settings()['course_ids']);
        $this->assertSame(0, app(RandomGenerator::class)->pool()->count());
    }

    public function test_settings_with_unknown_course_are_rejected(): void
    {
        $this->actingAsAdmin()
            ->post(route('dashboard.admin.randomGenerator.updateSettings'), [
                'participants' => 'first_years',
                'course_ids' => [999999],
                'opt_out_enabled' => false,
                'tutor_opt_out_enabled' => false,
                'hide_opted_out' => false,
                'hide_other_courses' => false,
                'hide_drawn' => false,
            ])
            ->assertSessionHasErrors('course_ids.0');

        $this->assertNull(State::where('key', RandomGenerator::SETTINGS_KEY)->first());
    }

    public function test_drawn_state_of_a_user_can_be_reset(): void
    {
        $user = User::factory()->create(['random_generator_drawn_at' => now()]);
        $otherUser = User::factory()->create(['random_generator_drawn_at' => now()]);

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.randomGenerator.resetDrawnUser', $user))
            ->assertRedirect(route('dashboard.admin.randomGenerator.index'))
            ->assertSessionHas('success');

        $this->assertNull($user->fresh()->random_generator_drawn_at);
        $this->assertNotNull($otherUser->fresh()->random_generator_drawn_at);
    }

    public function test_drawn_state_of_all_users_can_be_reset(): void
    {
        User::factory()->count(3)->create(['random_generator_drawn_at' => now()]);

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.randomGenerator.resetDrawn'))
            ->assertRedirect(route('dashboard.admin.randomGenerator.index'))
            ->assertSessionHas('success');

        $this->assertSame(0, User::whereNotNull('random_generator_drawn_at')->count());
    }

    public function test_display_excludes_users_that_opted_out_and_only_sends_public_attributes(): void
    {
        $this->admin->removeRole('tutor');
        $this->admin->givePermissionTo('manage random generator');
        $user = User::factory()->create();
        User::factory()->create(['random_generator_opt_out' => true]);

        $this->actingAsAdmin()
            ->get(route('dashboard.admin.randomGenerator.display'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Admin/RandomGenerator/Display')
                ->has('users', 1)
                ->has('users.0', fn (Assert $displayUser) => $displayUser
                    ->where('id', $user->id)
                    ->where('firstname', $user->firstname)
                    ->where('lastname', $user->lastname)
                    ->where('avatarUrl', null)
                )
            );
    }

    public function test_first_years_take_part_without_stored_settings(): void
    {
        $this->assertSame(RandomGenerator::PARTICIPANTS_FIRST_YEARS, app(RandomGenerator::class)->settings()['participants']);
    }

    public function test_tutors_can_be_drawn_instead_of_first_years(): void
    {
        $this->saveSettings(['participants' => RandomGenerator::PARTICIPANTS_TUTORS, 'course_ids' => [$this->course->id, $this->admin->course_id]]);

        $tutor = User::factory()->create(['course_id' => $this->course->id]);
        $tutor->assignRole('tutor');
        $stageTutor = User::factory()->create(['course_id' => $this->course->id]);
        $stageTutor->assignRole('stage tutor');
        $drawnTutor = User::factory()->create(['course_id' => $this->course->id, 'random_generator_drawn_at' => now()]);
        $drawnTutor->assignRole('tutor');
        $tutorOfOtherCourse = User::factory()->create(['course_id' => $this->otherCourse->id]);
        $tutorOfOtherCourse->assignRole('tutor');
        User::factory()->create(['course_id' => $this->course->id]);
        User::factory()->create()->assignRole('esa');

        $randomGenerator = app(RandomGenerator::class);

        // the admin is also a tutor
        $this->assertEqualsCanonicalizing(
            [$this->admin->id, $tutor->id, $stageTutor->id, $drawnTutor->id, $tutorOfOtherCourse->id],
            $randomGenerator->participants()->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$this->admin->id, $tutor->id, $stageTutor->id],
            $randomGenerator->pool()->pluck('id')->all(),
        );

        $this->actingAsAdmin()
            ->get(route('dashboard.admin.randomGenerator.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users', 5)
                ->where('poolCount', 3)
                ->where('settings.participants', 'tutors')
            );

        $this->actingAsAdmin()
            ->postJson(route('dashboard.admin.randomGenerator.indexExecuteSubmit'), ['state' => 'stopped'])
            ->assertJson(['success' => true]);

        $this->assertContains($this->randomGeneratorState()['user']['id'], [$this->admin->id, $tutor->id, $stageTutor->id]);
    }

    public function test_display_contains_first_years_and_tutors(): void
    {
        $firstYear = User::factory()->create();
        $tutor = User::factory()->create();
        $tutor->assignRole('tutor');
        User::factory()->create()->assignRole('esa');

        $this->actingAsAdmin()
            ->get(route('dashboard.admin.randomGenerator.display'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('users', fn ($users) => collect($users)->pluck('id')->sort()->values()->all() === collect([$this->admin->id, $firstYear->id, $tutor->id])->sort()->values()->all())
            );
    }

    public function test_settings_with_unknown_participants_are_rejected(): void
    {
        $this->actingAsAdmin()
            ->post(route('dashboard.admin.randomGenerator.updateSettings'), [
                'participants' => 'admins',
                'course_ids' => [],
                'opt_out_enabled' => false,
                'tutor_opt_out_enabled' => false,
                'hide_opted_out' => false,
                'hide_other_courses' => false,
                'hide_drawn' => false,
            ])
            ->assertSessionHasErrors('participants');

        $this->assertNull(State::where('key', RandomGenerator::SETTINGS_KEY)->first());
    }

    public function test_opt_out_page_is_not_reachable_if_the_opt_out_is_disabled(): void
    {
        $this->saveSettings(['opt_out_enabled' => false]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard.randomGenerator.index'))
            ->assertRedirect(route('dashboard.index'))
            ->assertSessionHas('info');

        $this->actingAs($user)
            ->post(route('dashboard.randomGenerator.update'), ['is_participating' => false])
            ->assertRedirect(route('dashboard.index'));

        $this->assertFalse($user->fresh()->random_generator_opt_out);
    }

    public function test_opt_out_page_is_not_reachable_if_the_module_is_inactive(): void
    {
        $this->saveSettings(['opt_out_enabled' => true]);
        Module::where('key', 'randomGenerator')->update(['active' => false]);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard.randomGenerator.index'))
            ->assertRedirect(route('app.index'));
    }

    public function test_user_can_opt_out_and_in_again(): void
    {
        $this->saveSettings(['opt_out_enabled' => true]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard.randomGenerator.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/RandomGenerator')
                ->where('isParticipating', true)
                ->where('submitUrl', '/dashboard/random-generator')
                ->where('randomGeneratorOptOutEnabled', true)
            );

        $this->actingAs($user)
            ->post(route('dashboard.randomGenerator.update'), ['is_participating' => false])
            ->assertRedirect(route('dashboard.randomGenerator.index'))
            ->assertSessionHas('success');

        $this->assertTrue($user->fresh()->random_generator_opt_out);

        $this->actingAs($user)
            ->post(route('dashboard.randomGenerator.update'), ['is_participating' => true])
            ->assertRedirect(route('dashboard.randomGenerator.index'));

        $this->assertFalse($user->fresh()->random_generator_opt_out);
    }

    public function test_user_that_opted_out_is_not_drawn(): void
    {
        $this->saveSettings(['opt_out_enabled' => true]);

        $user = User::factory()->create(['course_id' => $this->course->id]);

        $this->actingAs($user)
            ->post(route('dashboard.randomGenerator.update'), ['is_participating' => false]);

        $this->assertNull(app(RandomGenerator::class)->draw());
        $this->assertNull($user->fresh()->random_generator_drawn_at);
    }

    public function test_tutor_is_redirected_from_the_opt_out_page(): void
    {
        $this->saveSettings(['opt_out_enabled' => true]);

        $tutor = User::factory()->create();
        $tutor->assignRole('tutor');

        $this->actingAs($tutor)
            ->get(route('dashboard.randomGenerator.index'))
            ->assertRedirect(route('dashboard.tutor.index'));
    }

    public function test_tutor_can_opt_out_and_in_again(): void
    {
        $this->saveSettings(['tutor_opt_out_enabled' => true]);

        $tutor = User::factory()->create();
        $tutor->assignRole('tutor');

        $this->actingAs($tutor)->withSession(['tutor' => true])
            ->get(route('dashboard.tutor.randomGenerator.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/RandomGenerator')
                ->where('isParticipating', true)
                ->where('submitUrl', '/dashboard/tutor/random-generator')
                ->where('randomGeneratorTutorOptOutEnabled', true)
            );

        $this->actingAs($tutor)->withSession(['tutor' => true])
            ->post(route('dashboard.tutor.randomGenerator.update'), ['is_participating' => false])
            ->assertRedirect(route('dashboard.tutor.randomGenerator.index'))
            ->assertSessionHas('success');

        $this->assertTrue($tutor->fresh()->random_generator_opt_out);

        // a tutor that opted out is not drawn
        $this->saveSettings(['participants' => RandomGenerator::PARTICIPANTS_TUTORS, 'course_ids' => [$tutor->course_id, $this->admin->course_id], 'tutor_opt_out_enabled' => true]);
        $this->assertNotContains($tutor->id, app(RandomGenerator::class)->pool()->pluck('id')->all());

        $this->actingAs($tutor)->withSession(['tutor' => true])
            ->post(route('dashboard.tutor.randomGenerator.update'), ['is_participating' => true])
            ->assertRedirect(route('dashboard.tutor.randomGenerator.index'));

        $this->assertFalse($tutor->fresh()->random_generator_opt_out);
        $this->assertContains($tutor->id, app(RandomGenerator::class)->pool()->pluck('id')->all());
    }

    public function test_tutor_opt_out_has_its_own_setting(): void
    {
        // the opt out of the first-year students does not enable the opt out of the tutors
        $this->saveSettings(['opt_out_enabled' => true, 'tutor_opt_out_enabled' => false]);

        $tutor = User::factory()->create();
        $tutor->assignRole('tutor');

        $this->actingAs($tutor)->withSession(['tutor' => true])
            ->get(route('dashboard.tutor.randomGenerator.index'))
            ->assertRedirect(route('dashboard.tutor.index'))
            ->assertSessionHas('info');

        $this->actingAs($tutor)->withSession(['tutor' => true])
            ->post(route('dashboard.tutor.randomGenerator.update'), ['is_participating' => false])
            ->assertRedirect(route('dashboard.tutor.index'));

        $this->assertFalse($tutor->fresh()->random_generator_opt_out);

        // and the opt out of the tutors does not enable the opt out of the first-year students
        $this->saveSettings(['opt_out_enabled' => false, 'tutor_opt_out_enabled' => true]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard.randomGenerator.index'))
            ->assertRedirect(route('dashboard.index'));
    }

    public function test_first_year_student_can_not_use_the_tutor_opt_out(): void
    {
        $this->saveSettings(['tutor_opt_out_enabled' => true]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('dashboard.tutor.randomGenerator.update'), ['is_participating' => false])
            ->assertRedirect(route('dashboard.index'));

        $this->assertFalse($user->fresh()->random_generator_opt_out);
    }
}
