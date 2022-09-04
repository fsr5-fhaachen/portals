<?php

namespace App\Http\Controllers;

use App\Models\Event;
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
        if(!$event) {
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
     * Display the event index page 
     *
     * @param Request $request
     * 
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $event = $this->getEvent($request);
        if($event instanceof \Inertia\Response) {
            return $event;
        }
        $event->slots = $event->slots()->get();
        $registration = $event->registrations()->where('user_id', $request->user()->id)->first();

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
        if($event instanceof \Inertia\Response) {
            return $event;
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
        if($event instanceof \Inertia\Response) {
            return $event;
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

        // check if the user is already registered
        if($event->registrations()->where('user_id', auth()->user()->id)->exists()) {
            Session::flash('info', 'Du bist bereits für dieses Event angemeldet.');

            return $this->redirectToEvent($event);
        }

        $userRegistration = [
            'user_id' => auth()->user()->id,
        ];

        // check if event consider alcohol
        if($event->consider_alcohol) {
            $userRegistration['drinks_alcohol'] = !$request->drinks_no_alcohol;
        }

        // check if event has slots
        if($event->slots()->exists()) {
            // check if the user has selected a slot
            if(!$request->slot) {
                Session::flash('error', 'Du musst einen Slot auswählen.');

                return Redirect::back();
            }

            // check if the slot exists
            $slot = $event->slots()->find($request->slot);
            if(!$slot) {
                Session::flash('error', 'Der ausgewählte Slot existiert nicht.');

                return Redirect::back();
            }

            $userRegistration['slot_id'] = $slot->id;
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

        // check if the user is already registered
        if(!$event->registrations()->where('user_id', auth()->user()->id)->exists()) {
            Session::flash('info', 'Du bist nicht für dieses Event angemeldet.');

            return Redirect::back();
        }

        // unregister the user from the event
        $event->registrations()->where('user_id', auth()->user()->id)->delete();

        Session::flash('success', 'Du wurdest erfolgreich vom Event abgemeldet.');

        return Redirect::route('dashboard.index');
    }
}
