<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Inertia\Inertia;

class AppController extends Controller
{
  /**
   * Index page
   * 
   * @return \Inertia\Response
   */
  public function index()
  {
    // get courses ordered by name
    $courses = Course::orderBy('name')->get();

     return Inertia::render('Index', [
      'courses' => $courses
     ]);
  }
}
