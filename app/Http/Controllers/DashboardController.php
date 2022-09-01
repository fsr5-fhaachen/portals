<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the index page of 
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        return Inertia::render('Dashboard/Index');
    }
}
