<?php

use App\Helpers\SlotAssignment;
use App\Models\Event;
use App\Models\Slot;
use App\Models\User;
use App\Models\Course;
use App\Models\Registration;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // create a new event
    $this->event = new Event();
    $this->event->name = 'slot_booking_test';
    $this->event->description = 'Lorem ipsum';
    $this->event->type = 'slot_booking';

    $registration_from = new DateTime(NOW());
    $registration_to = new DateTime(NOW());
    $registration_to->modify('+7 days');

    $this->event->registration_from = $registration_from;
    $this->event->registration_to = $registration_to;
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
});

function createUsers(int $amount)
{
    return User::factory()
        ->has(
            Registration::factory()
                ->state([
                    'event_id' => test()->event->id,
                    'slot_id' =>  test()->slot->id,
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

test('assign with more registrations than free slots', function () {
    $reg_amount = 50;

    createUsers($reg_amount);

    // queue position should be equal to -1 before assignment                 
    expect($this->slot->registrations()->where('queue_position', -1)->get()->count())->toEqual($reg_amount);

    $this->assignment->assign();

    expect($this->slot->registrations()->where('queue_position', -1)->get()->count())->toEqual(0);

    // queue position should be null for assigned slots and > 1 for non-assigned slots
    $registrations = $this->slot->registrations()->orderBy('queue_position')->get();
    for ($i = 0; $i < $registrations->count(); $i++) {
        if ($i < 10) {
            expect($registrations[$i]->queue_position)->toBeNull();
            continue;
        }
        expect($registrations[$i]->queue_position)->toEqual($i - 9);
    }
});

test('assign with less registrations than slots', function () {
    $reg_amount = 5;

    createUsers($reg_amount);

    $this->assignment->assign();

    expect($this->slot->registrations()->where('queue_position', -1)->get()->count())->toEqual(0);

    // queue position should be null for all registrations
    expect($this->slot->registrations()->where('queue_position', null)->get()->count())->toEqual($reg_amount);
});

test('assign with exact registrations', function () {
    $reg_amount = 10;

    createUsers($reg_amount);

    $this->assignment->assign();

    expect($this->slot->registrations()->where('queue_position', -1)->get()->count())->toEqual(0);

    // queue position should be null for all registrations
    expect($this->slot->registrations()->where('queue_position', null)->get()->count())->toEqual($reg_amount);
});

test('reassign after new registrations', function () {
    $reg_amount = 5;
    $reg_amount_later = 12;

    createUsers($reg_amount);

    $this->assignment->assign();

    // queue position should be null for all registrations
    expect($this->slot->registrations()->where('queue_position', null)->get()->count())->toEqual($reg_amount);

    createUsers($reg_amount_later);
    $this->assignment->assign();
    expect($this->slot->registrations()->where('queue_position', -1)->get()->count())->toEqual(0);

    $registrations = $this->slot->registrations()->orderBy('queue_position')->get();
    for ($i = 0; $i < $registrations->count(); $i++) {
        if ($i < 10) {
            expect($registrations[$i]->queue_position)->toBeNull();
            continue;
        }
        expect($registrations[$i]->queue_position)->toEqual($i - 9);
    }
});
