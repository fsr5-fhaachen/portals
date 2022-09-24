<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Timeslot;
use Facade\FlareClient\Time\Time;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class AppController extends Controller
{
    public function index(Request $request)
    {
        // check if student is logged in and redirect to group page
        if ($request->session()->has('student')) {
            return Redirect::to('/group');
        }

        // check if tutor is logged in and redirect to group page
        if ($request->session()->has('tutor')) {
            return Redirect::to('/tutor/overview');
        }

        return Inertia::render('Index', [
            'timeslots' => Timeslot::orderBy('time')->get(),
        ]);
    }

    public function login(Request $request)
    {
        // check if student is logged in and redirect to group page
        if ($request->session()->has('student')) {
            return Redirect::to('/group');
        }

        // check if tutor is logged in and redirect to group page
        if ($request->session()->has('tutor')) {
            return Redirect::to('/tutor/overview');
        }

        return Inertia::render('Login', []);
    }


    public function loginStudent(Request $request)
    {
        // validate student data
        $validatedData = $request->validate([
            'email' => ['bail', 'required', 'max:100', 'email', 'exists:students'],
        ]);

        // get student
        $student = Student::where('email', $validatedData['email'])->first();

        // set student session
        $request->session()->put('student', $student->id);

        // redirect to group page
        return Redirect::route('group');
    }

    public function store(Request $request)
    {
        // validate student data
        if (Timeslot::count() > 0) {
            $validatedData = $request->validate([
                'firstname' => ['bail', 'required', 'max:30'],
                'lastname' => ['bail', 'required', 'max:30'],
                'email' => ['bail', 'required', 'max:100', 'email', 'unique:students'],
                'course' => ['bail', 'required', Rule::in(['ET', 'INF', 'MCD', 'WI'])],
                'timeslot_id' => ['bail', 'required', Rule::in(Timeslot::pluck('id'))],
            ]);
        } else {
            $validatedData = $request->validate([
                'firstname' => ['bail', 'required', 'max:30'],
                'lastname' => ['bail', 'required', 'max:30'],
                'email' => ['bail', 'required', 'max:100', 'email', 'unique:students'],
                'course' => ['bail', 'required', Rule::in(['ET', 'INF', 'MCD', 'WI'])],
            ]);
        }

        // create student
        $student = Student::create(
            $validatedData
        );

        // set student session
        $request->session()->put('student', $student->id);

        // redirect to group page
        return Redirect::route('group');
    }

    public function group(Request $request)
    {
        // check if student is not logged in and redirect to index page
        if ($request->session()->missing('student')) {
            return Redirect::to('/');
        }

        // get student
        $student = Student::find($request->session()->get('student'));

        // check if student was not found and forget session and redirect to index page
        if (is_null($student)) {
            $request->session()->forget('student');
            return Redirect::to('/');
        }

        return Inertia::render('Group', [
            'student' => $student,
            'group' => $student->group,
        ]);
    }
}
