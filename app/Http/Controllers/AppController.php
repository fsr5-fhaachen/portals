<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class AppController extends Controller
{
    public function index()
    {
        return Inertia::render('Index', []);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'student_firstname' => ['required', 'max:30'],
            'student_lastname' => ['required', 'max:30'],
            'student_email' => ['required', 'max:100', 'email'],
            'student_course' => ['required', Rule::in(['ET', 'INF', 'MCD', 'WI'])],
        ]);

        Student::create(
            $validatedData
        );

        return Redirect::route('group');
    }

    public function group()
    {
        return Inertia::render('Group', []);
    }
}
