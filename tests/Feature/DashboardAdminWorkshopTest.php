<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopAssignment;
use App\Models\WorkshopBlock;
use App\Models\WorkshopSession;
use Database\Seeders\CourseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

class DashboardAdminWorkshopTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Event $event;

    private WorkshopBlock $block;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([RoleSeeder::class, CourseSeeder::class]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(['admin', 'tutor']);

        $this->event = Event::factory()->workshopBooking(3)->create();
        $this->block = WorkshopBlock::factory()->for($this->event)->create(['name' => 'Vormittag']);
    }

    /**
     * Act as the admin that is logged in as tutor.
     */
    private function actingAsAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession(['tutor' => true]);
    }

    /**
     * Create a workshop with a session in the block of the event.
     */
    private function createWorkshop(?int $maximum = null): Workshop
    {
        $workshop = Workshop::factory()->for($this->event)->create();
        WorkshopSession::factory()->for($workshop)->for($this->block, 'block')->create(['maximum_participants' => $maximum]);

        return $workshop;
    }

    /**
     * Register a user for the event with the given workshops as priorities.
     *
     * @param  list<Workshop>  $workshops
     */
    private function register(array $workshops = []): Registration
    {
        $registration = $this->event->registrations()->create(['user_id' => User::factory()->create()->id]);

        foreach ($workshops as $index => $workshop) {
            $registration->workshopPriorities()->create(['workshop_id' => $workshop->id, 'priority' => $index + 1]);
        }

        return $registration;
    }

    public function test_user_without_admin_role_can_not_manage_workshops(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard.admin.workshops.index', $this->event))
            ->assertRedirect(route('dashboard.index'));
    }

    public function test_page_is_not_available_for_other_event_types(): void
    {
        $event = Event::factory()->create(['type' => 'slot_booking']);

        $this->actingAsAdmin()
            ->get(route('dashboard.admin.workshops.index', $event))
            ->assertNotFound();
    }

    public function test_index_shows_blocks_workshops_and_registrations(): void
    {
        $workshop = $this->createWorkshop();
        $registration = $this->register([$workshop]);

        $this->actingAsAdmin()
            ->get(route('dashboard.admin.workshops.index', $this->event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Admin/Workshops')
                ->has('blocks', 1)
                ->has('workshops', 1)
                ->has('workshops.0.sessions', 1)
                ->has('registrations', 1)
                ->where('registrations.0.id', $registration->id)
                ->where('registrations.0.priorities', [$workshop->id])
                ->where('blocks.0.assignments_count', 0)
                ->where('workshops.0.priorities_count', 1)
                ->where('workshops.0.sessions.0.assignments_count', 0)
            );
    }

    public function test_priority_count_can_only_be_changed_before_the_first_registration(): void
    {
        $this->actingAsAdmin()
            ->post(route('dashboard.admin.workshops.updateSettings', $this->event), ['workshop_priority_count' => 5])
            ->assertSessionHas('success');
        $this->assertSame(5, $this->event->fresh()->workshop_priority_count);

        $this->register();

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.workshops.updateSettings', $this->event), ['workshop_priority_count' => 2])
            ->assertSessionHas('error');
        $this->assertSame(5, $this->event->fresh()->workshop_priority_count);
    }

    public function test_blocks_can_be_created_updated_and_deleted(): void
    {
        $this->actingAsAdmin()
            ->post(route('dashboard.admin.workshops.storeBlock', $this->event), [
                'name' => 'Nachmittag',
                'starts_at' => '2026-11-10 14:00:00',
                'ends_at' => '2026-11-10 15:30:00',
            ])
            ->assertSessionHas('success');

        $block = $this->event->workshopBlocks()->where('name', 'Nachmittag')->sole();

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.workshops.updateBlock', [$this->event, $block]), [
                'name' => 'Abend',
                'starts_at' => '2026-11-10 18:00:00',
                'ends_at' => '2026-11-10 17:00:00',
            ])
            ->assertSessionHasErrors('ends_at');

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.workshops.updateBlock', [$this->event, $block]), [
                'name' => 'Abend',
                'starts_at' => '2026-11-10 18:00:00',
            ])
            ->assertSessionHas('success');
        $this->assertSame('Abend', $block->fresh()->name);

        $this->actingAsAdmin()
            ->delete(route('dashboard.admin.workshops.deleteBlock', [$this->event, $block]))
            ->assertSessionHas('success');
        $this->assertModelMissing($block);
    }

    public function test_block_times_from_the_browser_are_stored_in_the_app_timezone(): void
    {
        config(['app.timezone' => 'Europe/Berlin']);
        date_default_timezone_set('Europe/Berlin');

        try {
            $this->actingAsAdmin()
                ->post(route('dashboard.admin.workshops.updateBlock', [$this->event, $this->block]), [
                    'name' => 'Vormittag',
                    'starts_at' => '2026-11-12T08:00:00.000Z',
                ])
                ->assertSessionHas('success');

            $this->assertDatabaseHas('workshop_blocks', ['id' => $this->block->id, 'starts_at' => '2026-11-12 09:00:00']);
            $this->assertSame('2026-11-12T08:00:00.000000Z', $this->block->fresh()->starts_at->toJSON());
        } finally {
            date_default_timezone_set('UTC');
        }
    }

    public function test_block_of_another_event_can_not_be_changed(): void
    {
        $otherBlock = WorkshopBlock::factory()->create();

        $this->actingAsAdmin()
            ->delete(route('dashboard.admin.workshops.deleteBlock', [$this->event, $otherBlock]))
            ->assertNotFound();

        $this->assertModelExists($otherBlock);
    }

    public function test_workshop_is_created_and_updated_with_its_sessions(): void
    {
        $secondBlock = WorkshopBlock::factory()->for($this->event)->create();

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.workshops.storeWorkshop', $this->event), [
                'name' => 'Löten',
                'description' => 'Wir löten eine Platine.',
                'sessions' => [
                    ['workshop_block_id' => $this->block->id, 'location' => 'Raum 1', 'minimum_participants' => 3, 'maximum_participants' => 10],
                    ['workshop_block_id' => $secondBlock->id, 'location' => null, 'minimum_participants' => 0, 'maximum_participants' => null],
                ],
            ])
            ->assertSessionHas('success');

        $workshop = $this->event->workshops()->sole();
        $this->assertSame(2, $workshop->sessions()->count());
        $session = $workshop->sessions()->where('workshop_block_id', $this->block->id)->sole();
        $this->assertSame(['Raum 1', 3, 10], [$session->location, $session->minimum_participants, $session->maximum_participants]);

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.workshops.updateWorkshop', [$this->event, $workshop]), [
                'name' => 'Löten 2.0',
                'sessions' => [
                    ['workshop_block_id' => $secondBlock->id, 'location' => 'Labor', 'minimum_participants' => 1, 'maximum_participants' => 5],
                ],
            ])
            ->assertSessionHas('success');

        $this->assertSame('Löten 2.0', $workshop->fresh()->name);
        $this->assertModelMissing($session);
        $this->assertSame('Labor', $workshop->sessions()->sole()->location);
    }

    public function test_workshop_sessions_are_validated(): void
    {
        $otherBlock = WorkshopBlock::factory()->create();

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.workshops.storeWorkshop', $this->event), [
                'name' => 'Löten',
                'sessions' => [
                    ['workshop_block_id' => $this->block->id, 'minimum_participants' => 5, 'maximum_participants' => 2],
                    ['workshop_block_id' => $otherBlock->id, 'minimum_participants' => 0],
                ],
            ])
            ->assertSessionHasErrors(['sessions.0.maximum_participants', 'sessions.1.workshop_block_id']);

        $this->assertSame(0, $this->event->workshops()->count());
    }

    public function test_workshop_can_be_deleted(): void
    {
        $workshop = $this->createWorkshop();

        $this->actingAsAdmin()
            ->delete(route('dashboard.admin.workshops.deleteWorkshop', [$this->event, $workshop]))
            ->assertSessionHas('success');

        $this->assertModelMissing($workshop);
        $this->assertDatabaseCount('workshop_sessions', 0);
    }

    public function test_run_assigns_the_registrations_and_reports_a_summary(): void
    {
        $workshop = $this->createWorkshop();
        $registration = $this->register([$workshop]);

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.workshops.run', $this->event))
            ->assertSessionHas('success', 'Die Einteilung ist abgeschlossen. Prio 1: 1. Ohne Workshop: 0. Ausgefallene Termine: 0.');

        $this->assertSame(1, $registration->workshopAssignments()->count());
    }

    public function test_run_without_wishes_reports_that_nobody_was_assigned(): void
    {
        $this->createWorkshop();

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.workshops.run', $this->event))
            ->assertSessionHas('success', 'Die Einteilung ist abgeschlossen. Es wurde niemand eingeteilt. Ohne Workshop: 0. Ausgefallene Termine: 0.');
    }

    public function test_run_is_audited_on_the_event(): void
    {
        $this->register([$this->createWorkshop()]);

        $this->actingAsAdmin()->post(route('dashboard.admin.workshops.run', $this->event));

        $audit = Audit::where('event', 'workshopsAssigned')->sole();
        $this->assertSame($this->event->id, (int) $audit->auditable_id);
        $this->assertSame($this->admin->id, (int) $audit->user_id);
        $this->assertSame(['assigned' => 1, 'unassigned' => 0, 'cancelled_sessions' => 0], $audit->new_values);
    }

    public function test_run_is_not_possible_while_published(): void
    {
        $this->register([$this->createWorkshop()]);
        $this->event->update(['workshops_published_at' => now()]);

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.workshops.run', $this->event))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('workshop_assignments', 0);
    }

    public function test_assignment_can_be_published_and_unpublished(): void
    {
        $this->actingAsAdmin()
            ->post(route('dashboard.admin.workshops.publish', $this->event))
            ->assertSessionHas('success');
        $this->assertTrue($this->event->fresh()->areWorkshopsPublished());

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.workshops.unpublish', $this->event))
            ->assertSessionHas('success');
        $this->assertFalse($this->event->fresh()->areWorkshopsPublished());
    }

    public function test_registration_can_be_assigned_moved_and_removed_manually(): void
    {
        $first = $this->createWorkshop();
        $second = $this->createWorkshop();
        $registration = $this->register();

        foreach ([$first, $second] as $workshop) {
            $this->actingAsAdmin()
                ->post(route('dashboard.admin.workshops.assign', $this->event), [
                    'registration_id' => $registration->id,
                    'workshop_block_id' => $this->block->id,
                    'workshop_session_id' => $workshop->sessions()->sole()->id,
                ])
                ->assertSessionHas('success');
        }

        $this->assertSame($second->sessions()->sole()->id, $registration->workshopAssignments()->sole()->workshop_session_id);

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.workshops.assign', $this->event), [
                'registration_id' => $registration->id,
                'workshop_block_id' => $this->block->id,
                'workshop_session_id' => null,
            ])
            ->assertSessionHas('success');

        $this->assertSame(0, $registration->workshopAssignments()->count());
    }

    public function test_manual_assignment_to_a_full_session_is_a_warning(): void
    {
        $workshop = $this->createWorkshop(1);
        $session = $workshop->sessions()->sole();
        WorkshopAssignment::create(['registration_id' => $this->register()->id, 'workshop_session_id' => $session->id, 'workshop_block_id' => $this->block->id]);

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.workshops.assign', $this->event), [
                'registration_id' => $this->register()->id,
                'workshop_block_id' => $this->block->id,
                'workshop_session_id' => $session->id,
            ])
            ->assertSessionHas('success')
            ->assertSessionHas('warning', 'Der Termin hat jetzt 2 von maximal 1 Personen.');

        $this->assertSame(2, $session->assignments()->count());
    }

    public function test_manual_assignment_prevents_the_same_workshop_twice(): void
    {
        $secondBlock = WorkshopBlock::factory()->for($this->event)->create();
        $workshop = $this->createWorkshop();
        $secondSession = WorkshopSession::factory()->for($workshop)->for($secondBlock, 'block')->create();
        $registration = $this->register();
        WorkshopAssignment::create(['registration_id' => $registration->id, 'workshop_session_id' => $workshop->sessions()->where('workshop_block_id', $this->block->id)->sole()->id, 'workshop_block_id' => $this->block->id]);

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.workshops.assign', $this->event), [
                'registration_id' => $registration->id,
                'workshop_block_id' => $secondBlock->id,
                'workshop_session_id' => $secondSession->id,
            ])
            ->assertSessionHas('error');

        $this->assertSame(1, $registration->workshopAssignments()->count());
    }

    public function test_manual_assignment_validates_that_the_session_belongs_to_the_block(): void
    {
        $secondBlock = WorkshopBlock::factory()->for($this->event)->create();
        $workshop = $this->createWorkshop();

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.workshops.assign', $this->event), [
                'registration_id' => $this->register()->id,
                'workshop_block_id' => $secondBlock->id,
                'workshop_session_id' => $workshop->sessions()->sole()->id,
            ])
            ->assertSessionHasErrors('workshop_session_id');

        $this->assertDatabaseCount('workshop_assignments', 0);
    }

    public function test_admin_assigns_a_user_directly_to_workshops(): void
    {
        $secondBlock = WorkshopBlock::factory()->for($this->event)->create();
        $session = $this->createWorkshop()->sessions()->sole();
        $user = User::factory()->create();

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.assignUser'), [
                'email' => $user->email,
                'event_id' => $this->event->id,
                'workshop_sessions' => [$this->block->id => $session->id, $secondBlock->id => ''],
            ])
            ->assertSessionHas('success');

        $registration = $this->event->registrations()->sole();
        $this->assertSame($user->id, $registration->user_id);
        $this->assertSame([$this->block->id => $session->id], $registration->workshopAssignments->pluck('workshop_session_id', 'workshop_block_id')->all());
        $this->assertSame(0, $registration->workshopPriorities()->count());
        $this->assertNull($registration->form_responses);
    }

    public function test_admin_can_not_assign_a_user_to_the_same_workshop_twice(): void
    {
        $secondBlock = WorkshopBlock::factory()->for($this->event)->create();
        $workshop = $this->createWorkshop();
        $secondSession = WorkshopSession::factory()->for($workshop)->for($secondBlock, 'block')->create();

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.assignUser'), [
                'email' => User::factory()->create()->email,
                'event_id' => $this->event->id,
                'workshop_sessions' => [$this->block->id => $workshop->sessions()->where('workshop_block_id', $this->block->id)->sole()->id, $secondBlock->id => $secondSession->id],
            ])
            ->assertSessionHasErrors("workshop_sessions.{$secondBlock->id}");

        $this->assertSame(0, $this->event->registrations()->count());
        $this->assertDatabaseCount('workshop_assignments', 0);
    }

    public function test_admin_can_not_assign_a_user_to_a_session_of_another_block(): void
    {
        $secondBlock = WorkshopBlock::factory()->for($this->event)->create();
        $session = $this->createWorkshop()->sessions()->sole();

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.assignUser'), [
                'email' => User::factory()->create()->email,
                'event_id' => $this->event->id,
                'workshop_sessions' => [$secondBlock->id => $session->id],
            ])
            ->assertSessionHasErrors("workshop_sessions.{$secondBlock->id}");

        $this->assertSame(0, $this->event->registrations()->count());
    }

    public function test_admin_can_not_assign_a_user_to_a_workshop_of_another_event(): void
    {
        $otherSession = WorkshopSession::factory()
            ->for(Workshop::factory()->for(Event::factory()->workshopBooking()))
            ->create();

        $this->actingAsAdmin()
            ->post(route('dashboard.admin.assignUser'), [
                'email' => User::factory()->create()->email,
                'event_id' => $this->event->id,
                'workshop_sessions' => [$this->block->id => $otherSession->id],
            ])
            ->assertSessionHasErrors("workshop_sessions.{$this->block->id}");

        $this->assertSame(0, $this->event->registrations()->count());
    }
}
