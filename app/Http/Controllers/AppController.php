<?php

namespace App\Http\Controllers;

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
     return Inertia::render('Index');
  }
}
