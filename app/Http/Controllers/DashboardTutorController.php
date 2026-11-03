<?php

namespace App\Http\Controllers;

use App\Helpers\Table\EventPageTable;
use App\Helpers\Table\EventsTable;
use App\Helpers\Table\RegistrationsTable;
use App\Models\Course;
use App\Models\Event;
use App\Models\Group;
use App\Models\Slot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardTutorController extends Controller
{
    /**
     * Display the dashboard tutor index page
     */
    public function index(Request $request): Response|JsonResponse
    {
        $eventsTable = new EventsTable($request);
        if ($eventsTable->wantsChanges()) {
            return $eventsTable->changesResponse();
        }

        return Inertia::render('Dashboard/Tutor/Index', [
            'events' => $eventsTable->scroll(),
        ]);
    }

    /**
     * Display the dashboard tutor event page
     */
    public function event(Request $request): Response|JsonResponse
    {
        $event = Event::find($request->event);
        if (! $event) {
            return Inertia::render('Dashboard/404');
        }

        $eventPageTable = EventPageTable::for($request, $event);
        if ($eventPageTable && $eventPageTable[1]->wantsChanges()) {
            return $eventPageTable[1]->changesResponse();
        }

        $courses = Course::all();

        return Inertia::render('Dashboard/Tutor/Event', [
            'event' => $event,
            'courses' => $courses,
            ...($eventPageTable ? [$eventPageTable[0] => $eventPageTable[1]->scroll()] : []),
            'hasFormResponses' => $eventPageTable && $eventPageTable[1] instanceof RegistrationsTable && $eventPageTable[1]->hasFormResponses(),
        ]);
    }

    /**
     * Display the dashboard tutor slot page
     */
    public function slot(Request $request): Response|JsonResponse
    {
        $slot = Slot::with(['event', 'event.slots:id,event_id,name', 'event.groups:id,event_id,name'])->find($request->slot);
        if (! $slot) {
            return Inertia::render('Dashboard/404');
        }

        $registrationsTable = new RegistrationsTable($request, slotId: $slot->id);
        if ($registrationsTable->wantsChanges()) {
            return $registrationsTable->changesResponse();
        }

        $courses = Course::all();

        return Inertia::render('Dashboard/Tutor/Slot', [
            'slot' => $slot,
            'courses' => $courses,
            'registrations' => $registrationsTable->scroll(),
            'hasFormResponses' => $registrationsTable->hasFormResponses(),
        ]);
    }

    /**
     * Display the dashboard tutor group page
     */
    public function group(Request $request): Response|JsonResponse
    {
        $group = Group::with(['event', 'event.slots:id,event_id,name', 'event.groups:id,event_id,name'])->find($request->group);
        if (! $group) {
            return Inertia::render('Dashboard/404');
        }

        $registrationsTable = new RegistrationsTable($request, groupId: $group->id);
        if ($registrationsTable->wantsChanges()) {
            return $registrationsTable->changesResponse();
        }

        $courses = Course::all();

        return Inertia::render('Dashboard/Tutor/Group', [
            'group' => $group,
            'courses' => $courses,
            'registrations' => $registrationsTable->scroll(),
            'hasFormResponses' => $registrationsTable->hasFormResponses(),
        ]);
    }
}
