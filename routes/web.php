<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\TutorController;
use App\Http\Controllers\TutorGroupController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', [AppController::class, 'index']);
Route::get('/group/', [HomeController::class, 'group']);

Route::get('/tutor/', [TutorController::class, 'index']);

Route::get('/tutor/group/', [TutorGroupController::class, 'index']);
Route::get('/tutor/group/create/', [TutorGroupController::class, 'create']);
Route::get('/tutor/group/finish/', [TutorGroupController::class, 'finish']);