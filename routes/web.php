<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\TutorController;
use App\Http\Controllers\TutorGroupController;
use App\Http\Controllers\AdminGroupController;
use App\Http\Controllers\DatabaseTestController;
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
Route::get('/group/', [AppController::class, 'group']);

Route::get('/tutor/', [TutorController::class, 'index']);

Route::get('/tutor/group/', [TutorGroupController::class, 'index']);
Route::get('/tutor/group/create/', [TutorGroupController::class, 'create']);
Route::get('/tutor/group/finish/', [TutorGroupController::class, 'finish']);

//Routes to test factories. REMOVE BEFORE DEPLOYMENT
Route::get('/testfactorystudent', function() {
    $student = \App\Models\Student::factory()->make();
    return $student;
});
Route::get('/testfactorytutor', function() {
    $tutor = \App\Models\Tutor::factory()->make();
    return $tutor;
});
Route::get('/testfactorygroup', function() {
    $group = \App\Models\Group::factory()->make();
    return $group;
});
Route::get('/testfactorystation', function() {
    $station = \App\Models\Station::factory()->make();
    return $station;
});
Route::get('/testfactorytimeslot', function() {
    $timeslot = \App\Models\Timeslot::factory()->make();
    return $timeslot;
});

//Routes to test database. REMOVE BEFORE DEPLOYMENT
Route::get('/cleartable/all', [DatabaseTestController::class, 'clearAllTables']);
Route::get('/cleartable/{tableName}', [DatabaseTestController::class, 'clearTable']);
Route::get('/randomfill/{tableName}/{amount}', [DatabaseTestController::class, 'randomFillTable']);

Route::get('/students/{attr}/{val?}', [DatabaseTestController::class, 'getStudentsBy']);
Route::get('/tutors/{attr}/{val?}', [DatabaseTestController::class, 'getTutorsBy']);

Route::get('/resetassign', [AdminGroupController::class, 'resetGroupAssignment']);
Route::get('/assign/{groupSize}', [AdminGroupController::class, 'randAssignmentGroupPhase']);
