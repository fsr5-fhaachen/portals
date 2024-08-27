<?php

namespace App\Http\Controllers;

use App\Helpers\GroupBalancedDivision;
use App\Helpers\GroupCourseDivision;
use App\Helpers\SlotAssignment;
use App\Models\Course;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class DashboardAdminController extends Controller
{
    /**
     * Display the dashboard admin page
     */
    public function index(): Response
    {
        $courses = Course::all();

        foreach ($courses as $course) {
            $course->users = $course->users()->doesntHave('roles')->get();
        }

        return Inertia::render('Dashboard/Admin/Index', [
            'courses' => $courses,
        ]);
    }

    /**
     * Display the dashboard admin users page
     */
    public function users(): Response
    {
        $roles = Role::where('name', '!=', 'super admin')->get();
        $coures = Course::all();

        return Inertia::render('Dashboard/Admin/Users', [
            'roles' => $roles,
            'courses' => $coures,
        ]);
    }

    /**
     * Submit edit a user
     */
    public function editUser(IlluminateRequest $request): RedirectResponse
    {
        $user = User::find($request->user);

        if (! $user) {
            Session::flash('error', 'Der angegebene User existiert nicht');

            return Redirect::back();
        }

        // validate the request
        $validated = Request::validate([
            'firstname' => ['required', 'string', 'min:2', 'max:255'],
            'lastname' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'min:3', 'max:255', 'unique:users,email,' . $user->id],
            'email_confirm' => ['required', 'string', 'email', 'min:3', 'max:255', 'same:email'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'role_id' => ['array'],
            'is_disabled' => ['boolean'],
            'remove_avatar' => ['boolean'],
            'avatar' => ['nullable', 'string'],
        ]);

        // check if all roles exists and not super admin if so add to roles array
        $roles = [];
        if (array_key_exists('role_id', $validated) && ! $user->hasRole('super admin')) {
            foreach ($validated['role_id'] as $role) {
                if (! Role::find($role)) {
                    Session::flash('error', 'Die angegebene Rolle existiert nicht');

                    return Redirect::back();
                }
                $roleObject = Role::find($role);
                if ($roleObject->name == 'super admin') {
                    Session::flash('error', 'Der User kann nicht zu Super Admin gemacht werden');

                    return Redirect::back();
                }
                $roles[] = $role;
            }
        }

        // check if remove_avatar is set
        if (array_key_exists('remove_avatar', $validated) && $validated['remove_avatar']) {
            if ($user->avatar) {
                // delete old avatar
                Storage::disk('s3')->delete($user->avatar);
            }

            // set avatar to null
            $validated['avatar'] = null;
        }

        // remove email_confirm, role_id and remove_avatar from array
        unset($validated['email_confirm']);
        unset($validated['role_id']);
        unset($validated['remove_avatar']);

        // update the user
        $user->update($validated);

        // sync roles
        if (! $user->hasRole('super admin')) {
            $user->syncRoles($roles);
        }

        Session::flash('success', 'Der Account <strong>' . $user->email . '</strong> wurde erfolgreich bearbeitet. Die Tabelle aktualisiert sich in wenigen Sekunden automatisch.');

        return Redirect::back();
    }

    /**
     * Submit delete a user
     */
    public function deleteUser(IlluminateRequest $request): RedirectResponse
    {
        $user = User::find($request->user);

        if (! $user) {
            Session::flash('error', 'Der angegebene User existiert nicht');

            return Redirect::back();
        }

        // check if user has super admin role
        if ($user->hasRole('super admin')) {

            Session::flash('error', 'Der User kann nicht gelöscht werden');

            return Redirect::back();
        }

        // copy user to temp user variable
        $userTemp = $user;

        // delete the user
        $user->delete();

        // delete avatar
        if ($userTemp->avatar) {
            Storage::disk('s3')->delete($userTemp->avatar);
        }

        Session::flash('success', 'Der Account <strong>' . $userTemp->email . '</strong> wurde erfolgreich gelöscht. Die Tabelle aktualisiert sich in wenigen Sekunden automatisch.');

        return Redirect::back();
    }

    /**
     * Display the dashboard admin registrations page
     */
    public function registrations(IlluminateRequest $request): Response
    {
        $event = Event::with('groups')->with('slots')->find($request->event);
        if (! $event) {
            return Inertia::render('Dashboard/404');
        }
        $event->registrations = $event->registrations()->with('user')->get();

        $courses = Course::all();

        return Inertia::render('Dashboard/Admin/Registrations', [
            'event' => $event,
            'courses' => $courses,
        ]);
    }

    /**
     * Display the dashboard admin event page
     */
    public function event(IlluminateRequest $request): Response
    {
        $event = Event::find($request->event);
        if (! $event) {
            return Inertia::render('Dashboard/404');
        }
        $event->slots = $event->slots()->with('registrations')->get();
        $event->groups = $event->groups()->with('registrations')->get();
        $event->registrations = $event->registrations()->with('user')->get();

        $courses = Course::all();

        return Inertia::render('Dashboard/Admin/Event', [
            'event' => $event,
            'courses' => $courses,
        ]);
    }

    /**
     * Display the dashboard admin event submit page
     */
    public function eventSubmit(IlluminateRequest $request): Response
    {
        $event = Event::find($request->event);
        if (! $event) {
            return Inertia::render('Dashboard/404');
        }

        $courses = Course::all();

        // check if any groups has a course
        $hasCourse = false;
        foreach ($event->groups as $group) {
            if ($group->course_id) {
                $hasCourse = true;
                break;
            }
        }

        return Inertia::render('Dashboard/Admin/Submit', [
            'event' => $event,
            'courses' => $courses,
            'hasCourse' => $hasCourse,
        ]);
    }

    /**
     * Execute the dashboard admin event submit action
     */
    public function eventExecuteSubmit(IlluminateRequest $request): RedirectResponse
    {
        $event = Event::find($request->event);
        if (! $event) {
            Session::flash('error', 'Das angegebene Event existiert nicht');

            return Redirect::back();
        }

        // check event type
        if ($event->type == 'group_phase') {
            // check if any groups has a course
            $hasCourse = false;
            foreach ($event->groups as $group) {
                if ($group->course_id) {
                    $hasCourse = true;
                    break;
                }
            }

            // check which division method to use
            if ($hasCourse) {
                $courses = Course::all();

                foreach ($courses as $course) {
                    // check if groups is
                    $groups = $event->groups()->where('course_id', $course->id)->get();

                    if (count($groups) > 0) {
                        // get max_groups and max_participants for course by request
                        $maxGroups = $request->input('max_groups_' . $course->id);
                        $maxParticipants = $request->input('max_participants_' . $course->id);

                        $groupCourseDivision = new GroupCourseDivision($event, $course, $event->consider_alcohol, (int) $maxGroups, (int) $maxParticipants);
                        $groupCourseDivision->assign();
                    }
                }

                Session::flash('success', 'Die Gruppen wurden erfolgreich nach Studiengängen aufgeteilt');
            } else {
                // get max_groups and max_participants
                $maxGroups = $request->max_groups;
                $maxParticipants = $request->max_participants;

                $groupBalancedDivision = new GroupBalancedDivision($event, $event->consider_alcohol, (int) $maxGroups, (int) $maxParticipants);
                $groupBalancedDivision->assign();

                Session::flash('success', 'Die Gruppen wurden erfolgreich aufgeteilt');
            }

            return Redirect::route('dashboard.admin.event.index', ['event' => $event->id]);
        } elseif ($event->type == 'slot_booking') {
            // foreach event slots
            foreach ($event->slots as $slot) {
                $slotAssignment = new SlotAssignment($slot);
                $slotAssignment->assign();
            }

            Session::flash('success', 'Die Slots wurden erfolgreich zugewiesen');

            return Redirect::route('dashboard.admin.event.index', ['event' => $event->id]);
        } else {
            Session::flash('error', 'Das angegebene Event ist nicht vom Typ "group_phase" oder "slot_booking"');

            return Redirect::back();
        }
    }

    /**
     * Display the register page
     */
    public function register(Request $request): Response
    {
        // get courses ordered by name
        $courses = Course::orderBy('name')->get();

        // get all events with slots
        $events = Event::with('slots')->get();

        return Inertia::render('Dashboard/Admin/Register', [
            'courses' => $courses,
            'events' => $events,
        ]);
    }

    /**
     * Register a new user
     */
    public function registerUser(): RedirectResponse
    {
        // check if user with email already exists
        $user = User::where('email', Request::input('email'))->first();
        if ($user) {
            Session::flash('info', 'Der Account existiert bereits.');

            return Redirect::back();
        }

        // validate the request
        $validated = Request::validate([
            'firstname' => ['required', 'string', 'min:2', 'max:255'],
            'lastname' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'min:3', 'max:255', 'unique:users'],
            'email_confirm' => ['required', 'string', 'email', 'min:3', 'max:255', 'same:email'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'avatar' => ['nullable', 'string'],
        ]);

        // remove email_confirm from array
        unset($validated['email_confirm']);

        // create the user
        $user = User::create($validated);

        Session::flash('success', 'Der Account <strong>' . $user->email . '</strong> wurde erfolgreich erstellt.');

        return Redirect::back();
    }

    // TODO: Refactor this (╯°□°)╯︵ ┻━┻
    /**
     * Assign a new user
     */
    public function assignUser(): RedirectResponse
    {
        // check if user with email not exists
        $user = User::where('email', Request::input('email'))->first();
        if (! $user) {
            Session::flash('error', 'Der Account existiert nicht.');

            return Redirect::back();
        }

        // get event
        $event = Event::find(Request::input('event_id'));
        if (! $event) {
            Session::flash('error', 'Das Event existiert nicht.');

            return Redirect::back();
        }

        // validate the request
        $userRegistration = Request::validate([
            'email' => ['required', 'string', 'email', 'min:3', 'max:255', 'exists:users,email'],
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'slot_id' => ['integer', 'exists:slots,id'],
        ]);

        // check for existing registration for this event and user
        $existingRegistration = Registration::where('user_id', $user->id)->where('event_id', $event->id)->first();
        if ($existingRegistration) {
            Session::flash('error', 'Der Account ist bereits für dieses Event registriert.');

            return Redirect::back();
        }

        // check if event consider alcohol
        if ($event->consider_alcohol) {
            $userRegistration['drinks_alcohol'] = Request::input('drinks_no_alcohol');
        }

        // set default queue position
        $queuePosition = null;

        // check if slot is set
        if (array_key_exists('slot_id', $userRegistration)) {
            // get slot
            $slot = Slot::find($userRegistration['slot_id']);

            // check if slot exists
            if (! $slot) {
                Session::flash('error', 'Das Slot existiert nicht.');

                return Redirect::back();
            }

            // check if slot has maximum_participants
            if ($slot->maximum_participants) {
                $queuePosition = Registration::where('event_id', $event->id)->where('slot_id', $userRegistration['slot_id'])->max('queue_position');

                if (! $queuePosition || $queuePosition == -1) {
                    $queuePosition = -1;
                } else {
                    $queuePosition++;
                }

                $userRegistration['queue_position'] = $queuePosition;
            }
        } else {
            $queuePosition = Registration::where('event_id', $event->id)->max('queue_position');

            if ($queuePosition == -1) {
                $queuePosition = -1;
            } elseif ($queuePosition > 0) {
                $queuePosition++;
            }
        }

        // create registration
        Registration::create([
            'user_id' => $user->id,
            'event_id' => $userRegistration['event_id'],
            'slot_id' => (array_key_exists('slot_id', $userRegistration) ? $userRegistration['slot_id'] : null),
            'drinks_alcohol' => (array_key_exists('drinks_alcohol', $userRegistration) ? $userRegistration['drinks_alcohol'] : null),
            'queue_position' => $queuePosition,
        ]);

        Session::flash('success', 'Der Account <strong>' . $user->email . '</strong> wurde erfolgreich für das Event <strong>' . $event->name . '</strong>' . (array_key_exists('slot_id', $userRegistration) ? ' zu dem Slot <strong>' . $slot->name . '</strong>' : '') . ' zugewiesen.');

        return Redirect::back();
    }
}
