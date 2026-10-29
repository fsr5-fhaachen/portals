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
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Models\Audit;

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
        $registration = Registration::with('group', 'slot')->find($request->registration);
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
        $event = Event::with([
            'registrations.user',
            'slots' => fn ($query) => $query->withCount('registrations'),
            'groups' => fn ($query) => $query->withCount('registrations'),
        ])->find($request->event);

        if (! $event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $result = [
            'amount' => $event->registrations->count(),
        ];

        // add slot amounts
        foreach ($event->slots as $slot) {
            $result['slots'][$slot->id] = $slot->registrations_count;
        }

        // add group amounts
        foreach ($event->groups as $group) {
            $result['groups'][$group->id] = $group->registrations_count;
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
        $events = Event::with('registrations.user')->get();
        $courses = Course::all();

        $result = [];
        foreach ($events as $event) {
            $eventResult = [
                'id' => $event->id,
                'amount' => $event->registrations->count(),
            ];

            // add course amounts of registrations for this event by user course
            foreach ($courses as $course) {
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
        $courses = Course::withCount(['users as amount' => function ($query) {
            $query->doesntHave('roles');
        }])->get(['id']);

        return response()->json($courses->map(fn ($course) => [
            'id' => $course->id,
            'amount' => $course->amount,
        ]));
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

        // get all user ids of this event
        $userIds = $event->registrations()->pluck('user_id');

        $courses = Course::withCount(['users as amount' => function ($query) use ($userIds) {
            $query->doesntHave('roles')->whereIn('id', $userIds);
        }])->get(['id']);

        return response()->json($courses->map(fn ($course) => [
            'id' => $course->id,
            'amount' => $course->amount,
        ]));
    }

    /**
     * Return statistics for a given course
     * The statistics are counted by the registrations of the event.
     * Returns a JSON object, that containts amounts of each value and the name of the event of the form responses as well as the amount of users that drink alcohol.
     */
    public function courseStatistics(Request $request): JsonResponse
    {
        $event = Event::with(['registrations.user'])->find($request->event);

        if (! $event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $result = [];
        if ($event->consider_alcohol === true) {
            $result['drinks_alcohol'] = [
                'true' => $event->registrations->where('drinks_alcohol', true)->count(),
                'false' => $event->registrations->where('drinks_alcohol', false)->count(),
                'name' => 'Drinks Alcohol',
            ];
        }

        $formData = $event->registrations->pluck('form_responses')->filter(function ($item) {
            return ! is_null($item);
        });

        // Count statistics from formData
        foreach ($formData as $data) {
            if (is_string($data)) {
                $data = json_decode($data, true);
            }
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if (! isset($result[$key])) {
                        $result[$key] = [
                            $value => 1,
                            'name' => $key,
                        ];
                    } else {
                        if (isset($result[$key][$value])) {
                            $result[$key][$value]++;
                        } else {
                            $result[$key][$value] = 1;
                        }
                    }
                }
            }
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
     * Return the current state of the countdown.
     * The state is structured like this:
     *   {
     *     "state": string; // setup, idle, running, stopped
     *     "direction": string; // up, down
     *     "time": {
     *       "seconds": number;
     *       "minutes": number;
     *       "hours": number;
     *     };
     *   }
     *
     * The definition of the states is as follows:
     *   setup: The countdown is not set up yet
     *   idle: The countdown was resetted
     *   running: The countdown is running
     *   stopped: The countdown is stopped
     */
    public function countdownState(): JsonResponse
    {
        // get state with key countdown
        $state = State::where('key', 'countdown')->first();

        // if state does not exist, return setup
        if (! $state) {
            return response()->json([
                'state' => 'setup',
                'time' => [
                    'seconds' => 0,
                    'minutes' => 0,
                    'hours' => 0,
                ],
                'direction' => 'up',
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
            $user->avatarUrl = $user->avatarUrlUnchecked();

            return $user;
        });

        return response()->json([
            'users' => $users,
        ]);
    }

    /**
     * Details for a specific user: registrations, tutor assignments and audits
     */
    public function userDetails(User $user): JsonResponse
    {
        $registrations = $user->registrations()
            ->with(['event.groups', 'event.slots', 'slot', 'group'])
            ->get();

        $groupTutors = $user->groupTutors()->with('group.event')->get();
        $stationTutors = $user->stationTutors()->with('station.event')->get();

        return response()->json([
            'registrations' => $registrations,
            'groupTutors' => $groupTutors,
            'stationTutors' => $stationTutors,
            'audits' => $this->userAudits($user, $registrations->pluck('id')),
        ]);
    }

    /**
     * Update a registration of a specific user
     */
    public function userRegistrationUpdate(Request $request, User $user, Registration $registration): JsonResponse
    {
        $validated = $request->validate([
            'group_id' => ['nullable', 'integer', Rule::exists('groups', 'id')->where('event_id', $registration->event_id)],
            'slot_id' => ['nullable', 'integer', Rule::exists('slots', 'id')->where('event_id', $registration->event_id)],
            'is_present' => ['required', 'boolean'],
            'drinks_alcohol' => ['nullable', 'boolean'],
            'fulfils_requirements' => ['nullable', 'boolean'],
            'queue_position' => ['nullable', 'integer', 'min:-1'],
            'form_responses' => ['nullable', 'array'],
        ]);

        if (array_key_exists('form_responses', $validated) && empty($validated['form_responses'])) {
            $validated['form_responses'] = null;
        }

        $registration->update($validated);

        return response()->json([
            'registration' => $registration->fresh(['event.groups', 'event.slots', 'slot', 'group']),
        ]);
    }

    /**
     * Delete a registration of a specific user
     */
    public function userRegistrationDestroy(User $user, Registration $registration): JsonResponse
    {
        $registration->delete();

        return response()->json(['message' => 'Registration deleted']);
    }

    /**
     * Collect the latest audits of a user and of all registrations the user ever had
     *
     * @param  Collection<int, int>  $registrationIds
     * @return Collection<int, array{id: int, event: string, type: string, label: string, old_values: array<string, mixed>, new_values: array<string, mixed>, causer: ?string, created_at: string}>
     */
    private function userAudits(User $user, Collection $registrationIds): Collection
    {
        $hiddenAttributes = ['remember_token', 'pin'];

        // include registrations that were deleted in the meantime
        $registrationIds = Audit::where('auditable_type', Registration::class)
            ->where('event', 'created')
            ->where('new_values->user_id', $user->id)
            ->pluck('auditable_id')
            ->merge($registrationIds)
            ->unique();

        $audits = Audit::with('user')
            ->where(function ($query) use ($user, $registrationIds) {
                $query->where(function ($query) use ($user) {
                    $query->where('auditable_type', User::class)->where('auditable_id', $user->id);
                })->orWhere(function ($query) use ($registrationIds) {
                    $query->where('auditable_type', Registration::class)->whereIn('auditable_id', $registrationIds);
                });
            })
            ->latest('id')
            ->limit(100)
            ->get();

        $registrationEventIds = Registration::whereIn('id', $registrationIds)->pluck('event_id', 'id');
        $eventIds = $audits->flatMap(fn (Audit $audit) => [
            $audit->old_values['event_id'] ?? null,
            $audit->new_values['event_id'] ?? null,
        ])->merge($registrationEventIds)->filter()->unique();
        $eventNames = Event::whereIn('id', $eventIds)->pluck('name', 'id');

        return $audits->map(function (Audit $audit) use ($hiddenAttributes, $eventNames, $registrationEventIds) {
            $label = 'User';
            if ($audit->auditable_type === Registration::class) {
                $eventId = $audit->new_values['event_id']
                    ?? $audit->old_values['event_id']
                    ?? $registrationEventIds[$audit->auditable_id]
                    ?? null;
                $label = 'Anmeldung '.($eventNames[$eventId] ?? '#'.$audit->auditable_id);
            }

            return [
                'id' => $audit->id,
                'event' => $audit->event,
                'type' => class_basename($audit->auditable_type),
                'label' => $label,
                'old_values' => Arr::except($audit->old_values, $hiddenAttributes),
                'new_values' => Arr::except($audit->new_values, $hiddenAttributes),
                'causer' => $audit->user ? $audit->user->firstname.' '.$audit->user->lastname : null,
                'created_at' => $audit->created_at->toISOString(),
            ];
        })->values();
    }

    /**
     * Generate a presigned URL for avatar upload
     */
    public function generatePresignedUrlForAvatarUpload(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image',
        ]);

        $uuid = Str::uuid()->toString();
        $fileName = $uuid.'.'.$request->avatar->extension();
        $path = 'avatars/'.$fileName;
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
