<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopAssignment;
use App\Models\WorkshopBlock;
use App\Models\WorkshopSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TutorWorkshopSessionTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;

    private Event $event;

    private WorkshopSession $morningSession;

    private WorkshopSession $afternoonSession;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBasics();

        $this->tutor = $this->createUserWithRoles('tutor');
        $this->event = Event::factory()->workshopBooking(2)->create();

        $morning = WorkshopBlock::factory()->for($this->event)->create(['name' => 'Vormittag', 'starts_at' => now()->addDay()->setTime(10, 0)]);
        $afternoon = WorkshopBlock::factory()->for($this->event)->create(['name' => 'Nachmittag', 'starts_at' => now()->addDay()->setTime(14, 0)]);
        $origami = Workshop::factory()->for($this->event)->create(['name' => 'Origami']);

        // created in reverse order, the table orders by time
        $this->afternoonSession = WorkshopSession::factory()->for($origami)->for($afternoon, 'block')->create(['location' => 'Raum 2']);
        $this->morningSession = WorkshopSession::factory()->for($origami)->for($morning, 'block')->create(['location' => 'Raum 1', 'maximum_participants' => 10]);
    }

    /**
     * Register a participant for the event and assign them to the session.
     */
    private function assignedRegistration(WorkshopSession $session): Registration
    {
        $registration = Registration::factory()->create(['event_id' => $this->event->id, 'user_id' => User::factory()->create()->id]);
        WorkshopAssignment::factory()->create(['registration_id' => $registration->id, 'workshop_session_id' => $session->id]);

        return $registration;
    }

    public function test_event_page_lists_the_workshop_sessions_by_time(): void
    {
        $this->assignedRegistration($this->morningSession);
        $this->assignedRegistration($this->morningSession);

        $this->actingAsTutor($this->tutor)
            ->get(route('dashboard.tutor.event.index', $this->event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Tutor/Event')
                ->missing('registrations')
                ->has('workshopSessions.data', 2)
                ->where('workshopSessions.data.0.id', $this->morningSession->id)
                ->where('workshopSessions.data.0.workshop_name', 'Origami')
                ->where('workshopSessions.data.0.block_name', 'Vormittag')
                ->where('workshopSessions.data.0.location', 'Raum 1')
                ->where('workshopSessions.data.0.maximum_participants', 10)
                ->where('workshopSessions.data.0.assignments_count', 2)
                ->where('workshopSessions.data.1.id', $this->afternoonSession->id)
                ->where('workshopSessions.data.1.assignments_count', 0)
            );
    }

    public function test_session_page_lists_only_the_participants_of_the_session(): void
    {
        $morningParticipant = $this->assignedRegistration($this->morningSession);
        $this->assignedRegistration($this->afternoonSession);
        Registration::factory()->create(['event_id' => $this->event->id, 'user_id' => User::factory()->create()->id]);

        $this->actingAsTutor($this->tutor)
            ->get(route('dashboard.tutor.workshopSession.index', $this->morningSession))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Tutor/WorkshopSession')
                ->where('workshopSession.id', $this->morningSession->id)
                ->where('workshopSession.workshop.name', 'Origami')
                ->where('workshopSession.block.name', 'Vormittag')
                ->where('event.id', $this->event->id)
                ->has('registrations.data', 1)
                ->where('registrations.data.0.id', $morningParticipant->id)
            );
    }

    public function test_session_page_of_an_unknown_session_shows_not_found(): void
    {
        $this->actingAsTutor($this->tutor)
            ->get(route('dashboard.tutor.workshopSession.index', 999999))
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard/404'));
    }

    public function test_session_page_is_only_for_tutors(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard.tutor.workshopSession.index', $this->morningSession))
            ->assertRedirect();
    }
}
