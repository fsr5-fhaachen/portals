<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Event;
use Illuminate\Http\Request;
use App\Models\Registration;

class ApiController extends Controller
{
    /**
     * Display the index page
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function ping()
    {
        return response()->json(['message' => 'pong']);
    }

    /**
     * Return a requested registration if it exists and the user is allowed to see it
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function registrationsShow(Request $request)
    {
        // get registration
        $registration = Registration::find($request->registration);
        if (!$registration) {
            return response()->json(['message' => 'Registration not found'], 404);
        }

        // check if user is allowed to see the registration
        if ($registration->user_id != $request->user()->id) {
            return response()->json(['message' => 'Not allowed'], 403);
        }

        return response()->json($registration);
    }

    /**
     * Return the amount of registrations for a given event
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function eventRegistrationsAmount(Request $request)
    {
        $event = Event::find($request->event);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $result = [
            'amount' => $event->registrations()->count(),
        ];

        // add slot amounts
        foreach ($event->slots as $slot) {
            $result['slots'][$slot->id] = $slot->registrations()->count();
        }

        // add group amounts
        foreach ($event->groups as $group) {
            $result['groups'][$group->id] = $group->registrations()->count();
        }

        // add course amounts of registrations for this event by user course
        foreach (Course::all() as $course) {
            $result['courses'][$course->id] = 0;
        }
        foreach ($event->registrations as $registration) {
            $result['courses'][$registration->user->course_id]++;
        }

        return response()->json($result);
    }


    /**
     * Return the amount of registrations for all events
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function eventsRegistrationsAmount()
    {
        $events = Event::all();

        $result = [];
        foreach ($events as $event) {
            $eventResult = [
                'id' => $event->id,
                'amount' => $event->registrations->count(),
            ];

            // add course amounts of registrations for this event by user course
            foreach (Course::all() as $course) {
                $eventResult['courses'][$course->id] = 0;
            }
            foreach ($event->registrations as $registration) {
                $eventResult['courses'][$registration->user->course_id]++;
            }

            $result[] = $eventResult;
        }

        return response()->json($result);
    }

    /**
     * Return the registrations for a given event
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function eventRegistrationsShow(Request $request)
    {
        $event = Event::find($request->event);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $result = [
            'registrations' => $event->registrations()->with('user')->get(),
        ];

        // add slot amounts
        foreach ($event->slots as $slot) {
            $result['slots'][$slot->id] = $slot->registrations()->with('user')->get();
        }

        // add group amounts
        foreach ($event->groups as $group) {
            $result['groups'][$group->id] = $group->registrations()->with('user')->get();
        }

        // add course amounts of registrations for this event by user course
        foreach (Course::all() as $course) {
            $result['courses'][$course->id] = [];
        }
        foreach ($event->registrations()->with('user')->get() as $registration) {
            $result['courses'][$registration->user->course_id][] = $registration;
        }

        return response()->json($result);
    }

    /**
     * Toggle is_present for a given registration
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function registrationToggleIsPresent(Request $request)
    {
        $registration = Registration::find($request->registration);

        if (!$registration) {
            return response()->json(['message' => 'Registration not found'], 404);
        }

        $registration->is_present = !$registration->is_present;
        $registration->save();

        return response()->json($registration);
    }
}
