<?php

namespace Tests\Feature\Api;

use App\Models\Course;
use App\Models\Event;
use App\Models\Group;
use App\Models\GroupTutor;
use App\Models\Registration;
use App\Models\Slot;
use App\Models\Station;
use App\Models\StationTutor;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDetailsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $student;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        config(['audit.console' => true]);

        $this->seed(RoleSeeder::class);
        Course::factory()->create();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->student = User::factory()->create();
        $this->event = Event::factory()->create([
            'form' => '[{"$formkit": "text", "name": "eating_habit", "label": "Essgewohnheit"}]',
        ]);
    }

    /**
     * Act as the admin with an unlocked tutor session.
     */
    private function actingAsAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession(['tutor' => true]);
    }

    private function createRegistration(array $attributes = []): Registration
    {
        return Registration::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->student->id,
            ...$attributes,
        ]);
    }

    public function test_details_contain_registrations_tutor_assignments_and_audits(): void
    {
        $group = Group::factory()->create(['event_id' => $this->event->id]);
        $registration = $this->createRegistration([
            'group_id' => $group->id,
            'form_responses' => ['eating_habit' => 'vegan'],
        ]);
        $registration->update(['is_present' => true]);

        $station = Station::factory()->create(['event_id' => $this->event->id]);

        // tutor pivots are not incrementing, so they can not be audited on create
        config(['audit.console' => false]);
        GroupTutor::factory()->create(['user_id' => $this->student->id, 'group_id' => $group->id]);
        StationTutor::factory()->create(['user_id' => $this->student->id, 'station_id' => $station->id]);
        config(['audit.console' => true]);

        $response = $this->actingAsAdmin()->getJson(route('api.user.details', $this->student));

        $response->assertOk()
            ->assertJsonPath('registrations.0.id', $registration->id)
            ->assertJsonPath('registrations.0.form_responses.eating_habit', 'vegan')
            ->assertJsonPath('registrations.0.event.groups.0.id', $group->id)
            ->assertJsonPath('groupTutors.0.group.event.id', $this->event->id)
            ->assertJsonPath('stationTutors.0.station.id', $station->id)
            ->assertJsonPath('audits.0.event', 'updated')
            ->assertJsonPath('audits.0.label', 'Anmeldung '.$this->event->name)
            ->assertJsonPath('audits.0.new_values.is_present', true);
    }

    public function test_details_keep_audits_of_deleted_registrations_and_hide_sensitive_values(): void
    {
        $registration = $this->createRegistration();
        $registration->delete();
        $this->student->update(['remember_token' => 'secret']);

        $response = $this->actingAsAdmin()->getJson(route('api.user.details', $this->student));

        $response->assertOk()->assertJsonCount(0, 'registrations');

        $audits = collect($response->json('audits'));
        $this->assertTrue($audits->contains(fn (array $audit) => $audit['type'] === 'Registration' && $audit['event'] === 'deleted'));
        $this->assertFalse($audits->contains(fn (array $audit) => array_key_exists('remember_token', $audit['new_values'])));
    }

    public function test_details_require_manage_users_permission(): void
    {
        $tutor = User::factory()->create();
        $tutor->assignRole('tutor');

        $this->actingAs($tutor)->withSession(['tutor' => true])
            ->getJson(route('api.user.details', $this->student))
            ->assertForbidden();
    }

    public function test_registration_can_be_updated(): void
    {
        $group = Group::factory()->create(['event_id' => $this->event->id]);
        $slot = Slot::factory()->create(['event_id' => $this->event->id]);
        $registration = $this->createRegistration();

        $response = $this->actingAsAdmin()->putJson(route('api.user.registrations.update', [$this->student, $registration]), [
            'group_id' => $group->id,
            'slot_id' => $slot->id,
            'is_present' => true,
            'drinks_alcohol' => false,
            'fulfils_requirements' => true,
            'queue_position' => 3,
            'form_responses' => ['eating_habit' => 'all'],
        ]);

        $response->assertOk()->assertJsonPath('registration.group.id', $group->id);

        $registration->refresh();
        $this->assertSame($group->id, $registration->group_id);
        $this->assertSame($slot->id, $registration->slot_id);
        $this->assertTrue((bool) $registration->is_present);
        $this->assertFalse((bool) $registration->drinks_alcohol);
        $this->assertTrue((bool) $registration->fulfils_requirements);
        $this->assertSame(3, $registration->queue_position);
        $this->assertSame(['eating_habit' => 'all'], $registration->form_responses);
    }

    public function test_registration_update_stores_empty_form_responses_as_null(): void
    {
        $registration = $this->createRegistration(['form_responses' => ['eating_habit' => 'vegan']]);

        $this->actingAsAdmin()->putJson(route('api.user.registrations.update', [$this->student, $registration]), [
            'is_present' => false,
            'form_responses' => [],
        ])->assertOk();

        $this->assertNull($registration->refresh()->form_responses);
    }

    public function test_registration_update_rejects_group_and_slot_of_other_event(): void
    {
        $otherEvent = Event::factory()->create();
        $foreignGroup = Group::factory()->create(['event_id' => $otherEvent->id]);
        $foreignSlot = Slot::factory()->create(['event_id' => $otherEvent->id]);
        $registration = $this->createRegistration();

        $this->actingAsAdmin()->putJson(route('api.user.registrations.update', [$this->student, $registration]), [
            'group_id' => $foreignGroup->id,
            'slot_id' => $foreignSlot->id,
            'is_present' => false,
            'queue_position' => -2,
        ])->assertUnprocessable()->assertJsonValidationErrors(['group_id', 'slot_id', 'queue_position']);
    }

    public function test_registration_of_other_user_cannot_be_updated_or_deleted(): void
    {
        $otherUser = User::factory()->create();
        $registration = $this->createRegistration(['user_id' => $otherUser->id]);

        $this->actingAsAdmin()->putJson(route('api.user.registrations.update', [$this->student, $registration]), [
            'is_present' => true,
        ])->assertNotFound();

        $this->actingAsAdmin()->deleteJson(route('api.user.registrations.destroy', [$this->student, $registration]))
            ->assertNotFound();

        $this->assertModelExists($registration);
    }

    public function test_registration_can_be_deleted(): void
    {
        $registration = $this->createRegistration(['fulfils_requirements' => true]);

        $this->actingAsAdmin()->deleteJson(route('api.user.registrations.destroy', [$this->student, $registration]))
            ->assertOk();

        $this->assertModelMissing($registration);
    }
}
