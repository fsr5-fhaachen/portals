<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Station;
use App\Models\Timeslot;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class TutorController extends Controller
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

        return Inertia::render('Tutor/Index', []);
    }

    public function login(Request $request)
    {
        // validate student data
        $validatedData = $request->validate([
            'email' => ['bail', 'required', 'max:100', 'email', 'regex:/(^(.*)\@alumni\.fh\-aachen\.de$)/u', 'exists:tutors'],
            'password' => ['bail', Rule::in([config('app.tutor_password')])],
        ]);

        // get tutor
        $tutor = Tutor::where('email', $validatedData['email'])->first();

        // set tutor session
        $request->session()->put('tutor', $tutor->id);

        // redirect to tutor overview page
        return Redirect::route('tutor.overview');
    }

    public function overview(Request $request)
    {
        return Inertia::render('Tutor/Overview', [
            'tutors' => Tutor::all(),
            'stations' => Station::all(),
            'groups' => Group::all(),
            'tutor' => Tutor::find($request->session()->get('tutor')),
            'isAdmin' => (bool)Tutor::find($request->session()->get('tutor'))->is_admin,
            'showTimeslots' => (bool) Timeslot::count(),
        ]);
    }
}
