<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class DashboardEventController extends Controller
{
    /**
     * Return a event by request or redirect
     *
     * @param Request $request
     *
     * @return Event | \Inertia\Response
     */
    protected function getEvent(Request $request)
    {
        $event = Event::find($request->event);
        if (!$event) {
            return Inertia::render('Dashboard/404');
        }

        return $event;
    }

    /**
     * Redirect to the event page for the given event
     *
     * @param Event $event
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectToEvent(Event $event)
    {
        return Redirect::route('dashboard.event.index', ['event' => $event->id]);
    }

    /**
     * Redirect to the event page for the given event if the registration is not possible
     *
     * @param Event $event
     *
     * @return \Illuminate\Http\RedirectResponse|void
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
    }

    /**
     * Redirect to the event page for the given event if the unregistration is not possible
     *
     * @param Event $event
     *
     * @return \Illuminate\Http\RedirectResponse|void
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
     *
     * @param Request $request
     *
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $event = $this->getEvent($request);
        if ($event instanceof \Inertia\Response) {
            return $event;
        }

        $event->slots = $event->slots()->get();
        $registration = $event->registrations()->where('user_id', $request->user()->id)->first();
        $registration->group = $registration->group()->first();


        return Inertia::render('Dashboard/Event/Index', [
            'event' => $event,
            'registration' => $registration,
        ]);
    }

    /**
     * Display the event register page
     *
     * @param Request $request
     *
     * @return \Inertia\Response
     */
    public function register(Request $request)
    {
        $event = $this->getEvent($request);
        if ($event instanceof \Inertia\Response) {
            return $event;
        }
        $registrationIsPossible = $this->redirectToEventIfNoRegistrationIsPossible($event);
        if ($registrationIsPossible instanceof \Illuminate\Http\RedirectResponse) {
            return $registrationIsPossible;
        }
        $event->slots = $event->slots()->get();

        return Inertia::render('Dashboard/Event/Register', [
            'event' => $event
        ]);
    }

    /**
     * Display the event unregister page
     *
     * @param Request $request
     *
     * @return \Inertia\Response
     */
    public function unregister(Request $request)
    {
        $event = $this->getEvent($request);
        if ($event instanceof \Inertia\Response) {
            return $event;
        }
        $unregistrationIsPossible = $this->redirectToEventIfNoUnregistrationIsPossible($event);
        if ($unregistrationIsPossible instanceof \Illuminate\Http\RedirectResponse) {
            return $unregistrationIsPossible;
        }

        return Inertia::render('Dashboard/Event/Unregister', [
            'event' => $event
        ]);
    }

    /**
     * Register the user to the event
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function registerUser(Request $request)
    {
        $event = $this->getEvent($request);
        if ($event instanceof \Inertia\Response) {
            return $event;
        }
        $registrationIsPossible = $this->redirectToEventIfNoRegistrationIsPossible($event);
        if ($registrationIsPossible instanceof \Illuminate\Http\RedirectResponse) {
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
            $userRegistration['drinks_alcohol'] = !$request->drinks_no_alcohol;
        }

        // check if event has slots
        if ($event->slots()->exists()) {
            // check if the user has selected a slot
            if (!$request->slot) {
                Session::flash('error', 'Du musst einen Slot auswählen.');

                return Redirect::back();
            }

            // check if the slot exists
            $slot = $event->slots()->find($request->slot);
            if (!$slot) {
                Session::flash('error', 'Der ausgewählte Slot existiert nicht.');

                return Redirect::back();
            }

            $userRegistration['slot_id'] = $slot->id;

            // check if slot has maximum_participants
            if ($slot->maximum_participants) {
                $userRegistration['queue_position'] = -1;
            }
        }

        // get all other inputs
        $userRegistration['form_responses'] = $request->except(['_token', 'drinks_no_alcohol', 'slot']);

        // register the user to the event
        $event->registrations()->create($userRegistration);

        Session::flash('success', 'Du wurdest erfolgreich für das Event angemeldet.');

        return $this->redirectToEvent($event);
    }

    /**
     * Unregister the user from the event
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unregisterUser(Request $request)
    {
        $event = $this->getEvent($request);
        if ($event instanceof \Inertia\Response) {
            return $event;
        }
        $unregistrationIsPossible = $this->redirectToEventIfNoUnregistrationIsPossible($event);
        if ($unregistrationIsPossible instanceof \Illuminate\Http\RedirectResponse) {
            return $unregistrationIsPossible;
        }

        // check if the user is already registered
        $registration = $event->registrations()->where('user_id', auth()->user()->id)->first();
        if (!$registration->exists()) {
            Session::flash('info', 'Du bist nicht für dieses Event angemeldet.');

            return Redirect::back();
        }

        // get registration
        $registration = Registration::find($registration->id);

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
