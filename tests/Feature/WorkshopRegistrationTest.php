<?php

namespace Tests\Feature;

use App\Helpers\WorkshopDivision;
use App\Models\Event;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopBlock;
use App\Models\WorkshopSession;
use Database\Seeders\CourseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WorkshopRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private User $user;

    /**
     * @var list<Workshop>
     */
    private array $workshops;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([RoleSeeder::class, CourseSeeder::class]);

        $this->event = Event::factory()->workshopBooking(3)->create([
            'registration_from' => now()->subDay(),
            'registration_to' => now()->addDay(),
        ]);

        $block = WorkshopBlock::factory()->for($this->event)->create();
        $this->workshops = Workshop::factory()->count(4)->for($this->event)->create()->all();
        foreach ($this->workshops as $workshop) {
            WorkshopSession::factory()->for($workshop)->for($block, 'block')->create();
        }

        $this->user = User::factory()->create();
    }

    /**
     * Register the user with the given workshops as priorities.
     *
     * @param  list<int>  $workshopIds
     */
    private function registerWithPriorities(array $workshopIds): TestResponse
    {
        return $this->actingAs($this->user)
            ->from(route('dashboard.event.register', $this->event))
            ->post(route('dashboard.event.registerUser', $this->event), ['priorities' => $workshopIds]);
    }

    public function test_register_page_shows_the_workshops(): void
    {
        $this->actingAs($this->user)
            ->get(route('dashboard.event.register', $this->event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Event/Register')
                ->where('event.workshop_priority_count', 3)
                ->has('event.workshops', 4)
                ->has('event.workshops.0.sessions.0.block')
            );
    }

    public function test_user_registers_with_priorities(): void
    {
        $workshopIds = [$this->workshops[2]->id, $this->workshops[0]->id, $this->workshops[3]->id];

        $this->registerWithPriorities($workshopIds)
            ->assertRedirect(route('dashboard.event.index', $this->event))
            ->assertSessionHas('success');

        $registration = $this->event->registrations()->sole();
        $this->assertSame($workshopIds, $registration->workshopPriorities->pluck('workshop_id')->all());
        $this->assertSame([1, 2, 3], $registration->workshopPriorities->pluck('priority')->all());
        $this->assertNull($registration->form_responses);
    }

    public function test_priorities_must_be_complete_and_different(): void
    {
        $this->registerWithPriorities([$this->workshops[0]->id, $this->workshops[1]->id])
            ->assertRedirect(route('dashboard.event.register', $this->event))
            ->assertSessionHasErrors(['priorities' => 'Wähle 3 verschiedene Workshops aus.']);

        $this->registerWithPriorities([$this->workshops[0]->id, $this->workshops[0]->id, $this->workshops[1]->id])
            ->assertSessionHasErrors(['priorities.0' => 'Wähle 3 verschiedene Workshops aus.']);

        $this->assertSame(0, $this->event->registrations()->count());
    }

    public function test_registration_is_not_possible_without_priorities(): void
    {
        $this->actingAs($this->user)
            ->from(route('dashboard.event.register', $this->event))
            ->post(route('dashboard.event.registerUser', $this->event))
            ->assertRedirect(route('dashboard.event.register', $this->event))
            ->assertSessionHasErrors('priorities');

        $this->registerWithPriorities([])
            ->assertSessionHasErrors('priorities');

        $this->assertSame(0, $this->event->registrations()->count());
    }

    public function test_priorities_must_be_workshops_of_the_event(): void
    {
        $otherWorkshop = Workshop::factory()->create();

        $this->registerWithPriorities([$this->workshops[0]->id, $this->workshops[1]->id, $otherWorkshop->id])
            ->assertSessionHasErrors('priorities.2');

        $this->assertSame(0, $this->event->registrations()->count());
    }

    public function test_registration_is_not_possible_with_less_workshops_than_priorities(): void
    {
        $this->event->update(['workshop_priority_count' => 5]);

        $this->registerWithPriorities(collect($this->workshops)->pluck('id')->all())
            ->assertRedirect(route('dashboard.event.register', $this->event))
            ->assertSessionHasErrors(['priorities' => 'Für dieses Event gibt es noch nicht genug Workshops. Die Anmeldung ist erst möglich, wenn es mindestens 5 Workshops gibt.']);

        $this->assertSame(0, $this->event->registrations()->count());
    }

    public function test_registration_is_not_possible_without_workshops(): void
    {
        Workshop::query()->delete();

        $this->registerWithPriorities([])
            ->assertSessionHasErrors('priorities');

        $this->assertSame(0, $this->event->registrations()->count());
    }

    public function test_new_workshop_event_has_three_priorities(): void
    {
        $event = Event::factory()->create(['type' => 'workshop_booking']);

        $this->assertSame(3, $event->fresh()->workshop_priority_count);
    }

    public function test_unregister_deletes_the_priorities_and_assignments(): void
    {
        $this->registerWithPriorities([$this->workshops[0]->id, $this->workshops[1]->id, $this->workshops[2]->id]);
        (new WorkshopDivision($this->event, 1))->assign();

        $registration = $this->event->registrations()->sole();
        $this->assertSame(1, $registration->workshopAssignments()->count());

        $this->actingAs($this->user)
            ->post(route('dashboard.event.unregisterUser', $this->event))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('workshop_priorities', 0);
        $this->assertDatabaseCount('workshop_assignments', 0);
    }

    public function test_assignment_is_only_visible_after_publishing(): void
    {
        $this->registerWithPriorities([$this->workshops[0]->id, $this->workshops[1]->id, $this->workshops[2]->id]);
        (new WorkshopDivision($this->event, 1))->assign();

        $this->actingAs($this->user)
            ->get(route('dashboard.event.index', $this->event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Event/Index')
                ->has('event.workshop_blocks', 1)
                ->has('registration.workshop_priorities', 3)
                ->where('registration.workshop_priorities.0.workshop.id', $this->workshops[0]->id)
                ->missing('registration.workshop_assignments')
            );

        $this->event->update(['workshops_published_at' => now()]);

        $this->actingAs($this->user)
            ->get(route('dashboard.event.index', $this->event))
            ->assertInertia(fn (Assert $page) => $page
                ->has('registration.workshop_assignments', 1)
                ->where('registration.workshop_assignments.0.session.workshop.id', $this->workshops[0]->id)
            );
    }
}
