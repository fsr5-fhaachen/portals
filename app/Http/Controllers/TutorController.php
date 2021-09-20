<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TutorController extends Controller
{
    public function index()
    {
        return Inertia::render('Tutor/Index', []);
    }

    public function station()
    {
        return Inertia::render('Tutor/Station', []);
    }
}
