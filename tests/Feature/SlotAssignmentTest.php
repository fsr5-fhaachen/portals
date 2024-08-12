<?php

namespace Tests\Feature;

use App\Helpers\SlotAssignment;
use App\Models\Event;
use App\Models\Slot;
use App\Models\User;
use App\Models\Course;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use DateTime;

class SlotAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private $event;
    private $slot;
    private $assignment;

    public function setUp(): void
    {
        parent::setUp();

        // create a new event
        $this->event = new Event();
        $this->event->name = 'slot_booking_test';
        $this->event->description = 'Lorem ipsum';
        $this->event->type = 'slot_booking';

        $registrationFrom = new DateTime(NOW());
        $registrationTo = new DateTime(NOW());
        $registrationTo->modify('+7 days');

        $this->event->registration_from = $registrationFrom;
        $this->event->registration_to = $registrationTo;
        $this->event->has_requirements = false;
        $this->event->consider_alcohol = false;
        $this->event->sort_order = 300;
        $this->event->save();

        // cerate a new slot
        $this->slot = new Slot();
        $this->slot->name = "small_test";
        $this->slot->event_id = $this->event->id;
        $this->slot->has_requirements = false;
        $this->slot->maximum_participants = "10";
        $this->slot->save();

        Course::factory()->count(1)->create();

        $this->assignment = new SlotAssignment($this->slot);
    }

    private function createUsers(int $amount)
    {
        return User::factory()
            ->has(
                Registration::factory()
                    ->state([
                        'event_id' => $this->event->id,
                        'slot_id' =>  $this->slot->id,
                        'group_id' => null,
                        'drinks_alcohol' => true,
                        'fulfils_requirements' => false,
                        'is_present' => false,
                        'queue_position' => -1,
                    ])
                    ->count(1)
            )
            ->state(['course_id' => Course::first()->id])
            ->count($amount)
            ->create();
    }

    /**
     * A test for the user assignment to slots.
     */
    public function testAssignWithMoreRegistrationsThanFreeSlots(): void
    {
        $regAmount = 50;

        $this->createUsers($regAmount);

        // queue position should be equal to -1 before assignment                 
        $this->assertEquals($regAmount, $this->slot->registrations()->where('queue_position', -1)->get()->count());

        $this->assignment->assign();

        $this->assertEquals(0, $this->slot->registrations()->where('queue_position', -1)->get()->count());

        // queue position should be null for assigned slots and > 1 for non-assigned slots
        $registrations = $this->slot->registrations()->orderBy('queue_position')->get();
        for ($i = 0; $i < $registrations->count(); $i++) {
            if ($i < 10) {
                $this->assertNull($registrations[$i]->queue_position);
                continue;
            }
            $this->assertEquals($i - 9, $registrations[$i]->queue_position);
        }
    }

    public function testAssignWithLessRegistrationsThanSlots(): void
    {
        $regAmount = 5;

        $this->createUsers($regAmount);

        $this->assignment->assign();

        $this->assertEquals(0, $this->slot->registrations()->where('queue_position', -1)->get()->count());

        // queue position should be null for all registrations
        $this->assertEquals($regAmount, $this->slot->registrations()->where('queue_position', null)->get()->count());
    }

    public function testAssignWithExactRegistrations(): void
    {
        $regAmount = 10;

        $this->createUsers($regAmount);

        $this->assignment->assign();

        $this->assertEquals(0, $this->slot->registrations()->where('queue_position', -1)->get()->count());

        // queue position should be null for all registrations
        $this->assertEquals($regAmount, $this->slot->registrations()->where('queue_position', null)->get()->count());
    }

    public function testReassignAfterNewRegistrations(): void
    {
        $regAmount = 5;
        $regAmountLater = 12;

        $this->createUsers($regAmount);

        $this->assignment->assign();

        // queue position should be null for all registrations
        $this->assertEquals($regAmount, $this->slot->registrations()->where('queue_position', null)->get()->count());

        $this->createUsers($regAmountLater);
        $this->assignment->assign();
        $this->assertEquals(0, $this->slot->registrations()->where('queue_position', -1)->get()->count());

        $registrations = $this->slot->registrations()->orderBy('queue_position')->get();
        for ($i = 0; $i < $registrations->count(); $i++) {
            if ($i < 10) {
                $this->assertNull($registrations[$i]->queue_position);
                continue;
            }
            $this->assertEquals($i - 9, $registrations[$i]->queue_position);
        }
    }
}
