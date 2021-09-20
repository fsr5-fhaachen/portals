<?php

namespace App\Http\Controllers;

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
            'email' => ['bail', 'required', 'max:100', 'email', 'regex:/(^(.*)\@(ad\.|alumni\.|dialup\.|stud\.|)fh\-aachen\.de$)/u', 'exists:tutors'],
            'password' => ['bail', Rule::in([config('app.tutor_password')])],
        ]);

        // get tutor
        $tutor = Tutor::where('email', $validatedData['email'])->first();

        // set tutor session
        $request->session()->put('tutor', $tutor->id);

        // redirect to tutor overview page
        return Redirect::route('tutor.overview');
    }


    public function station()
    {
        return Inertia::render('Tutor/Station', []);
    }
}
