<?php

namespace App\Http\Controllers;

use App\Helpers\GroupBalancedDivision;
use App\Helpers\GroupCourseDivision;
use App\Helpers\SlotAssignment;
use App\Models\Course;
use App\Models\Event;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class DashboardAdminController extends Controller
{
    /**
     * Display the dashboard admin page
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $courses = Course::with('users')->get();

        return Inertia::render('Dashboard/Admin/Index', [
            'courses' => $courses,
        ]);
    }

    /**
     * Display the dashboard admin event page
     *
     * @param Request $request
     *
     * @return \Inertia\Response
     */
    public function event(Request $request)
    {
        $event = Event::find($request->event);
        if (!$event) {
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
     *
     * @param Request $request
     *
     * @return \Inertia\Response
     */
    public function eventSubmit(Request $request)
    {
        $event = Event::find($request->event);
        if (!$event) {
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
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function eventExecuteSubmit(Request $request)
    {
        $event = Event::find($request->event);
        if (!$event) {
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


                        $groupCourseDivision = new GroupCourseDivision($event, $course, $event->consider_alcohol, (int)$maxGroups, (int)$maxParticipants);
                        $groupCourseDivision->assign();
                    }
                }

                Session::flash('success', 'Die Gruppen wurden erfolgreich nach Studiengängen aufgeteilt');
            } else {
                // TODO: get max_groups and max_participants
                // $maxGroups = $request->max_groups;
                // $maxParticipants = $request->max_participants;

                $groupBalancedDivision = new GroupBalancedDivision($event, $event->consider_alcohol);
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
}
