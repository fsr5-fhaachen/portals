<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
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

  /**
   * Register a new user
   * 
   * @return \Illuminate\Http\RedirectResponse
   */
   public function registerUser()
   {
      // validate the request
      $validated = Request::validate([
         'firstname' => ['required', 'string', 'min:2', 'max:255'],
         'lastname' => ['required', 'string', 'min:2', 'max:255'],
         'email' => ['required', 'string', 'email', 'min:3', 'max:255', 'unique:users'],
         'email_confirm' => ['required', 'string', 'email', 'min:3', 'max:255', 'same:email'],
         'course_id' => ['required', 'integer', 'exists:courses,id'],
      ]);

      // remove email_confirm from array
      unset($validated['email_confirm']);

      // create the user
      $user = User::create($validated);

      // login the user
      Auth::login($user, true);

      return Redirect::route('dashboard.index');
   }
}
