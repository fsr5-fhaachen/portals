<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventParticipationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBasics();

        $this->admin = $this->createUserWithRoles('admin', 'tutor');
        $this->event = Event::factory()->create(['type' => 'event_registration']);
    }

    /**
     * Register the user for the event.
     */
    private function register(User $user, ?Event $event = null): Registration
    {
        return Registration::factory()->create(['event_id' => ($event ?? $this->event)->id, 'user_id' => $user->id]);
    }

    public function test_events_table_contains_the_share_of_registered_participants(): void
    {
        $participants = User::factory()->count(4)->create();
        $this->register($participants[0]);
        $this->register($participants[1]);
        // tutors are counted as registrations, but not for the share of the participants
        $this->register($this->createUserWithRoles('tutor'));

        $this->actingAsTutor($this->admin)
            ->get(route('dashboard.tutor.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('events.data.0.registrations_count', 3)
                ->where('events.data.0.participant_registrations_count', 2)
                ->where('events.data.0.participants_count', 4)
            );
    }

    public function test_event_page_contains_the_amount_of_participants(): void
    {
        User::factory()->count(3)->create();

        $this->actingAsTutor($this->admin)
            ->get(route('dashboard.admin.event.index', $this->event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('participantsCount', 3));
    }

    public function test_negative_list_contains_the_participants_that_are_not_registered(): void
    {
        $registered = User::factory()->create(['lastname' => 'Angemeldet']);
        $notRegistered = User::factory()->create(['lastname' => 'Bauer']);
        $registeredForOtherEvent = User::factory()->create(['lastname' => 'Christ']);
        $this->register($registered);
        $this->register($registeredForOtherEvent, Event::factory()->create());
        // tutors are no participants
        $this->createUserWithRoles('tutor');

        $this->actingAsTutor($this->admin)
            ->get(route('dashboard.admin.event.notRegistered', $this->event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Admin/NotRegistered')
                ->where('event.id', $this->event->id)
                ->has('users.data', 2)
                ->where('users.data.0.id', $notRegistered->id)
                ->where('users.data.1.id', $registeredForOtherEvent->id)
                ->has('users.data.0.course.abbreviation')
            );
    }

    public function test_negative_list_can_be_searched(): void
    {
        User::factory()->create(['firstname' => 'Anna', 'lastname' => 'Bauer']);
        $ben = User::factory()->create(['firstname' => 'Ben', 'lastname' => 'Koch']);

        $this->actingAsTutor($this->admin)
            ->get(route('dashboard.admin.event.notRegistered', ['event' => $this->event, 'search' => 'koch']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.id', $ben->id)
            );
    }

    public function test_live_update_removes_participants_that_registered(): void
    {
        $user = User::factory()->create();
        $this->register($user);

        $this->actingAsTutor($this->admin)
            ->getJson(route('dashboard.admin.event.notRegistered', ['event' => $this->event, 'changes_since' => now()->subMinute()->timestamp, 'loaded_ids' => (string) $user->id]))
            ->assertOk()
            ->assertJsonPath('removed_ids', [$user->id]);
    }

    public function test_negative_list_is_not_reachable_without_permission(): void
    {
        $this->actingAsTutor($this->createUserWithRoles('tutor'))
            ->get(route('dashboard.admin.event.notRegistered', $this->event))
            ->assertRedirect(route('dashboard.index'));
    }
}
