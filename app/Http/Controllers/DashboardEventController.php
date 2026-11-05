<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DashboardEventController extends Controller
{
    /**
     * Return a event by request or redirect
     *
     *
     * @return Event | Response
     */
    protected function getEvent(Request $request): Event
    {
        $event = Event::find($request->event);
        if (! $event) {
            return Inertia::render('Dashboard/404');
        }

        return $event;
    }

    /**
     * Redirect to the event page for the given event
     */
    protected function redirectToEvent(Event $event): RedirectResponse
    {
        return Redirect::route('dashboard.event.index', ['event' => $event->id]);
    }

    /**
     * Redirect to the event page for the given event if the registration is not possible
     *
     *
     * @return RedirectResponse|void
     */
    protected function redirectToEventIfNoRegistrationIsPossible(Event $event)
    {
        // check if registration is already open
        if ($event->registration_from && $event->registration_from->isFuture()) {
            Session::flash('error', 'Anmeldung ist noch nicht möglich');

            return $this->redirectToEvent($event);
        }

        // check if registration is already closed
        if ($event->registration_to && $event->registration_to->isPast()) {
            Session::flash('error', 'Anmeldung ist nicht mehr möglich');

            return $this->redirectToEvent($event);
        }

        // check if the user has the right courses to register
        if ($event->courses()->exists()) {
            if (! $event->courses()->where('course_id', Auth::user()->course_id)->exists()) {
                Session::flash('error', 'Du kannst dich mit deinem Studiengang zu diesem Event nicht anmelden.');

                return $this->redirectToEvent($event);
            }
        }
    }

    /**
     * Redirect to the event page for the given event if the unregistration is not possible
     *
     *
     * @return RedirectResponse|void
     */
    protected function redirectToEventIfNoUnregistrationIsPossible(Event $event)
    {
        // check if registration is already open
        if ($event->registration_from && $event->registration_from->isFuture()) {
            Session::flash('error', 'Abmeldung ist noch nicht möglich');

            return $this->redirectToEvent($event);
        }

        // check if registration is already closed
        if ($event->registration_to && $event->registration_to->isPast()) {
            Session::flash('error', 'Abmeldung ist nicht mehr möglich');

            return $this->redirectToEvent($event);
        }
    }

    /**
     * Display the event index page
     */
    public function index(Request $request): Response
    {
        $event = $this->getEvent($request);
        if ($event instanceof Response) {
            return $event;
        }

        $event->slots = $event->slots()->get();

        // get registration from user
        $registration = Registration::where('event_id', $event->id)->where('user_id', $request->user()->id)->first();

        if ($registration) {
            $registration->group = $registration->group()->first();
        }

        if ($event->type == 'workshop_booking') {
            $event->load('workshopBlocks');

            // the users only see their workshops after the assignment was published
            $registration?->load($event->areWorkshopsPublished()
                ? ['workshopPriorities.workshop', 'workshopAssignments.session.workshop']
                : ['workshopPriorities.workshop']);
        }

        return Inertia::render('Dashboard/Event/Index', [
            'event' => $event,
            'registration' => $registration,
        ]);
    }

    /**
     * Display the event register page
     */
    public function register(Request $request): Response|RedirectResponse
    {
        $event = $this->getEvent($request);
        if ($event instanceof Response) {
            return $event;
        }
        $registrationIsPossible = $this->redirectToEventIfNoRegistrationIsPossible($event);
        if ($registrationIsPossible instanceof RedirectResponse) {
            return $registrationIsPossible;
        }
        $event->slots = $event->slots()->get();

        if ($event->type == 'workshop_booking') {
            $event->load(['workshops.sessions.block', 'workshopBlocks']);
        }

        return Inertia::render('Dashboard/Event/Register', [
            'event' => $event,
        ]);
    }

    /**
     * Display the event unregister page
     */
    public function unregister(Request $request): Response|RedirectResponse
    {
        $event = $this->getEvent($request);
        if ($event instanceof Response) {
            return $event;
        }
        $unregistrationIsPossible = $this->redirectToEventIfNoUnregistrationIsPossible($event);
        if ($unregistrationIsPossible instanceof RedirectResponse) {
            return $unregistrationIsPossible;
        }

        return Inertia::render('Dashboard/Event/Unregister', [
            'event' => $event,
        ]);
    }

    /**
     * Register the user to the event
     */
    public function registerUser(Request $request): RedirectResponse
    {
        $event = $this->getEvent($request);
        if ($event instanceof Response) {
            return $event;
        }
        $registrationIsPossible = $this->redirectToEventIfNoRegistrationIsPossible($event);
        if ($registrationIsPossible instanceof RedirectResponse) {
            return $registrationIsPossible;
        }

        // check if the user is already registered
        if ($event->registrations()->where('user_id', auth()->user()->id)->exists()) {
            Session::flash('info', 'Du bist bereits für dieses Event angemeldet.');

            return $this->redirectToEvent($event);
        }

        $userRegistration = [
            'user_id' => auth()->user()->id,
        ];

        // check if event consider alcohol
        if ($event->consider_alcohol) {
            $userRegistration['drinks_alcohol'] = ! $request->drinks_no_alcohol;
        }

        // set default queue position
        $queuePosition = null;

        // check if event has slots
        if ($event->slots()->exists()) {
            // check if the user has selected a slot
            if (! $request->slot) {
                Session::flash('error', 'Du musst einen Slot auswählen.');

                return Redirect::back();
            }

            // check if the slot exists
            $slot = $event->slots()->find($request->slot);
            if (! $slot) {
                Session::flash('error', 'Der ausgewählte Slot existiert nicht.');

                return Redirect::back();
            }

            $userRegistration['slot_id'] = $slot->id;

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

        // the user chooses the workshops of a workshop booking by priority
        $workshopIds = $event->type == 'workshop_booking' ? $this->validatedWorkshopPriorities($request, $event) : [];

        // get all other inputs
        $form_responses = $request->except(['_token', 'drinks_no_alcohol', 'slot', 'priorities']);
        if ($form_responses) {
            $userRegistration['form_responses'] = $form_responses;
        }

        // register the user to the event
        try {
            DB::transaction(function () use ($event, $userRegistration, $workshopIds) {
                $registration = $event->registrations()->create($userRegistration);

                foreach ($workshopIds as $index => $workshopId) {
                    $registration->workshopPriorities()->create([
                        'workshop_id' => $workshopId,
                        'priority' => $index + 1,
                    ]);
                }
            });
        } catch (UniqueConstraintViolationException) {
            Session::flash('info', 'Du bist bereits für dieses Event angemeldet.');

            return $this->redirectToEvent($event);
        }

        Session::flash('success', 'Du wurdest erfolgreich für das Event angemeldet.');

        return $this->redirectToEvent($event);
    }

    /**
     * Validate the workshop priorities of the request. The user has to choose as many different workshops of the event as
     * the event has priorities, so the event needs at least that many workshops.
     *
     * @return list<int> the workshop ids ordered by priority
     *
     * @throws ValidationException
     */
    protected function validatedWorkshopPriorities(Request $request, Event $event): array
    {
        $workshopIds = $event->workshops()->pluck('id')->all();
        $requiredCount = $event->workshop_priority_count;

        if (count($workshopIds) < $requiredCount) {
            throw ValidationException::withMessages([
                'priorities' => 'Für dieses Event gibt es noch nicht genug Workshops. Die Anmeldung ist erst möglich, wenn es mindestens '.$requiredCount.' Workshops gibt.',
            ]);
        }

        $message = 'Wähle '.$requiredCount.' verschiedene Workshops aus.';

        $validated = Validator::make($request->only('priorities'), [
            'priorities' => ['required', 'array', 'size:'.$requiredCount],
            'priorities.*' => ['required', 'integer', 'distinct', Rule::in($workshopIds)],
        ], [
            'priorities.*' => $message,
            'priorities.*.*' => $message,
        ])->validate();

        return array_map('intval', array_values($validated['priorities']));
    }

    /**
     * Unregister the user from the event
     */
    public function unregisterUser(Request $request): RedirectResponse
    {
        $event = $this->getEvent($request);
        if ($event instanceof Response) {
            return $event;
        }
        $unregistrationIsPossible = $this->redirectToEventIfNoUnregistrationIsPossible($event);
        if ($unregistrationIsPossible instanceof RedirectResponse) {
            return $unregistrationIsPossible;
        }

        // check if the user is already registered
        $registration = $event->registrations()->where('user_id', auth()->user()->id)->first();
        if (! $registration) {
            Session::flash('info', 'Du bist nicht für dieses Event angemeldet.');

            return Redirect::back();
        }

        // check if user fulfils_requirements
        if ($registration->fulfils_requirements) {
            Session::flash('error', 'Du kannst dich nicht abmelden, da du bereits fest angemeldet bist.');

            return Redirect::back();
        }

        // delete the registration
        $registration->delete();

        Session::flash('success', 'Du wurdest erfolgreich vom Event abgemeldet.');

        return Redirect::route('dashboard.index');
    }
}
