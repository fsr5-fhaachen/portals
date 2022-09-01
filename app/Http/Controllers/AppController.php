<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Inertia\Inertia;

class AppController extends Controller
{
  /**
   * Display the index page
   * 
   * @return \Inertia\Response
   */
  public function index()
  {
     return Inertia::render('Index');
  }

  /**
   * Display the login page
   * 
   * @return \Inertia\Response
   */
  public function login()
  {
     return Inertia::render('Login');
  }

  /**
   * Display the register page
   * 
   * @return \Inertia\Response
   */
  public function register()
  {
    // get courses ordered by name
    $courses = Course::orderBy('name')->get();

     return Inertia::render('Register', [
      'courses' => $courses
     ]);
  }
}
