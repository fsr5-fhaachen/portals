<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Event;
use App\Models\Registration;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApiController extends Controller
{
    /**
     * Display the index page
     */
    public function ping(): JsonResponse
    {
        return response()->json(['message' => 'pong']);
    }

    /**
     * Return a requested registration if it exists and the user is allowed to see it
     */
    public function registrationsShow(Request $request): JsonResponse
    {
        // get registration
        $registration = Registration::with('group', 'slot')->get()->find($request->registration);
        if (! $registration) {
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
     */
    public function eventRegistrationsAmount(Request $request): JsonResponse
    {
        $event = Event::find($request->event);

        if (! $event) {
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
     * @param  Request  $request
     */
    public function eventsRegistrationsAmount(): JsonResponse
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
     */
    public function eventRegistrationsShow(Request $request): JsonResponse
    {
        $event = Event::find($request->event);

        if (! $event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $result = [];

        // TODO: optimize this
        // add registrations
        // $result['registrations'] = $event->registrations()->with('user')->get();

        // // add slot amounts
        // foreach ($event->slots as $slot) {
        //     $result['slots'][$slot->id] = $slot->registrations()->with('user')->get();
        // }

        // // add group amounts
        // foreach ($event->groups as $group) {
        //     $result['groups'][$group->id] = $group->registrations()->with('user')->get();
        // }

        // // add course amounts of registrations for this event by user course
        // foreach (Course::all() as $course) {
        //     $result['courses'][$course->id] = [];
        // }
        // foreach ($event->registrations()->with('user')->get() as $registration) {
        //     $result['courses'][$registration->user->course_id][] = $registration;
        // }

        return response()->json($result);
    }

    /**
     * Toggle is_present for a given registration
     */
    public function registrationsToggleIsPresent(Request $request): JsonResponse
    {
        $registration = Registration::find($request->registration);

        if (! $registration) {
            return response()->json(['message' => 'Registration not found'], 404);
        }

        $registration->is_present = ! $registration->is_present;
        $registration->save();

        return response()->json($registration);
    }

    /**
     * Toggle fulfils_requirements for a given registration
     */
    public function registrationsToggleFulfilsRequirements(Request $request): JsonResponse
    {
        $registration = Registration::find($request->registration);

        if (! $registration) {
            return response()->json(['message' => 'Registration not found'], 404);
        }

        $registration->fulfils_requirements = ! $registration->fulfils_requirements;
        $registration->save();

        return response()->json($registration);
    }

    /**
     * Delete a given registration
     */
    public function registrationsDestroy(Request $request): JsonResponse
    {
        $registration = Registration::find($request->registration);

        if (! $registration) {
            return response()->json(['message' => 'Registration not found'], 404);
        }

        // check if registration fullfills requirements
        if ($registration->fulfils_requirements) {
            return response()->json(['message' => 'Registration fullfills requirements'], 403);
        }

        $registration->delete();

        return response()->json(['message' => 'Registration deleted']);
    }

    /**
     * Return all courses with users amopunt
     *
     * @param  Request  $request
     */
    public function coursesUserAmount(): JsonResponse
    {
        $courses = Course::all();

        $result = [];

        foreach ($courses as $course) {
            $result[] = [
                'id' => $course->id,
                'amount' => $course->users()->doesntHave('roles')->count(),
            ];
        }

        return response()->json($result);
    }

    /**
     * Return all courses with users amount of event
     */
    public function coursesUserAmountPerEvent(Request $request): JsonResponse
    {
        $event = Event::find($request->event);

        if (! $event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $courses = Course::all();

        // get all user ids of this event
        $userIds = [];
        foreach ($event->registrations as $registration) {
            $userIds[] = $registration->user_id;
        }

        $result = [];

        foreach ($courses as $course) {
            $result[] = [
                'id' => $course->id,
                'amount' => $course->users()->doesntHave('roles')->whereIn('id', $userIds)->count(),
            ];
        }

        return response()->json($result);
    }

    /**
     * Return the current state of the random generator.
     * The state is structured like this:
     *   {
     *     "state": "setup", // setup, idle, running, stopped
     *     "user": null | User, // default null and if stopped, the user that was selected by the random generator
     *   }
     *
     * The definition of the states is as follows:
     *   setup: The random generator is not set up yet
     *   idle: The random generator is set up, but not running yet
     *   running: The random generator is running
     *   stopped: The random generator is stopped and a user was selected
     */
    public function randomGeneratorState(): JsonResponse
    {
        // get state with key randomGenerator
        $state = State::where('key', 'randomGenerator')->first();

        // if state does not exist, return setup
        if (! $state) {
            return response()->json([
                'state' => 'setup',
            ]);
        }

        return response()->json(json_decode($state->value));
    }

    /**
     * Return the current state of the score system.
     * The state is structured like this:
     *   {
     *     "teams": {
     *       "name": string;
     *       "score": string;
     *     }[];
     *   }
     */
    public function scoreSystemState(): JsonResponse
    {
        // get state with key scoreSystem
        $state = State::where('key', 'scoreSystem')->first();

        // if state does not exist, return setup
        if (! $state) {
            return response()->json([
                'teams' => [],
            ]);
        }

        return response()->json(json_decode($state->value));
    }

    /**
     * Fresh users data
     */
    public function users(): JsonResponse
    {
        $users = User::with('course', 'roles')->get()->map(function ($user) {
            $user->avatarUrl = $user->avatarUrl();

            return $user;
        });

        return response()->json([
            'users' => $users,
        ]);
    }

    /**
     * Generate a presigned URL for avatar upload
     */
    public function generatePresignedUrlForAvatarUpload(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image',
        ]);

        $uuid = Str::uuid()->toString();
        $fileName = $uuid . '.' . $request->avatar->extension();
        //$path = 'avatars/' . $user->id . '/' . $fileName;
        $path = 'avatars/' . $fileName;
        $presignedUrl = Storage::disk('s3')->temporaryUploadUrl(
            $path,
            now()->addMinutes(5)
        );

        return response()->json([
            'presignedUrl' => $presignedUrl,
            'path' => $path,
        ]);
    }
}
