<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Event;
use App\Models\Group;
use App\Models\Registration;
use App\Models\State;
use App\Models\Station;
use App\Models\TaskCompletion;
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
     * Registrations for a specific user
     */
    public function userRegistrations(User $user): JsonResponse
    {
        $registrations = $user->registrations()->with(['event', 'group'])->get();

        return response()->json([
            'registrations' => $registrations,
        ]);
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

    /**
     * Check if the given user is allowed to see the rally schedule of the given group
     * (either they're registered in it, or they're one of its group tutors).
     */
    private function canViewGroupRallyInfo(Group $group, User $user): bool
    {
        if ($group->tutors->contains($user->id)) {
            return true;
        }

        return $group->registrations()->where('user_id', $user->id)->exists();
    }

    /**
     * Return a group's station schedule: past stops, the current one, and future ones with
     * the station name masked until it's their turn.
     */
    public function groupCurrentStop(Request $request): JsonResponse
    {
        $group = Group::with('event')->find($request->group);
        if (! $group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        if (! $this->canViewGroupRallyInfo($group, $request->user())) {
            return response()->json(['message' => 'Not allowed'], 403);
        }

        $stops = $group->stops()->with('station')->orderBy('round')->get();
        $scoringEnabled = (bool) ($group->event->rally_config['scoring_enabled'] ?? false);

        $currentRound = null;
        foreach ($stops as $stop) {
            $completed = $scoringEnabled ? $stop->points !== null : ($stop->ends_at && $stop->ends_at->isPast());
            if (! $completed) {
                $currentRound = $stop->round;
                break;
            }
        }

        $result = $stops->map(function ($stop) use ($currentRound, $scoringEnabled) {
            $revealed = $currentRound === null || $stop->round <= $currentRound;
            $opponent = $stop->opponentStop();

            return [
                'round' => $stop->round,
                'starts_at' => $stop->starts_at,
                'ends_at' => $stop->ends_at,
                'station' => $revealed ? ['id' => $stop->station->id, 'name' => $stop->station->name, 'latitude' => $stop->station->latitude, 'longitude' => $stop->station->longitude] : null,
                'opponent_group_name' => $opponent?->group?->name,
                'points' => $scoringEnabled ? $stop->points : null,
                'bonus' => $scoringEnabled ? $stop->bonus : null,
                'is_current' => $stop->round === $currentRound,
            ];
        });

        return response()->json(['stops' => $result]);
    }

    /**
     * Return the current (unscored) duel at a station, for the tutor's live view.
     */
    public function stationCurrentDuel(Request $request): JsonResponse
    {
        $station = Station::find($request->station);
        if (! $station) {
            return response()->json(['message' => 'Station not found'], 404);
        }

        $currentRound = $station->stops()->whereNull('points')->min('round');

        $stops = $currentRound
            ? $station->stops()->where('round', $currentRound)->orderBy('group_id')->with('group')->get()
            : collect();

        return response()->json([
            'round' => $currentRound,
            'stops' => $stops,
        ]);
    }

    /**
     * Return a group's task checklist with completion state.
     */
    public function eventTasksState(Request $request): JsonResponse
    {
        $group = Group::with('event')->find($request->group);
        if (! $group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        if (! $this->canViewGroupRallyInfo($group, $request->user())) {
            return response()->json(['message' => 'Not allowed'], 403);
        }

        $tasks = $group->event->tasks()->with(['completions' => function ($query) use ($group) {
            $query->where('group_id', $group->id);
        }])->get();

        return response()->json(['tasks' => $tasks]);
    }

    /**
     * Return the task-race ranking for an event: groups that have completed every task,
     * ordered by when they finished the last one.
     */
    public function eventTaskRanking(Request $request): JsonResponse
    {
        $event = Event::find($request->event);
        if (! $event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $taskIds = $event->tasks()->pluck('id');
        $taskCount = $taskIds->count();

        if ($taskCount === 0) {
            return response()->json(['ranking' => []]);
        }

        $ranking = $event->groups()->get()
            ->map(function ($group) use ($taskIds, $taskCount) {
                $completions = TaskCompletion::where('group_id', $group->id)
                    ->whereIn('task_id', $taskIds)
                    ->whereNotNull('completed_at')
                    ->get();

                if ($completions->count() !== $taskCount) {
                    return null;
                }

                return ['group' => $group->only('id', 'name'), 'finished_at' => $completions->max('completed_at')];
            })
            ->filter()
            ->sortBy('finished_at')
            ->values();

        return response()->json(['ranking' => $ranking]);
    }
}
