<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
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
Route::get('/login', [AppController::class, 'login']);
Route::get('/register', [AppController::class, 'register']);

Route::group([
    'prefix' => 'dashboard',
], function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/test', [DashboardController::class, 'test']);
});


// TODO: remove devlopment routes
Route::group([
    'prefix' => 'dev',
], function () {
    //Routes to test database. REMOVE BEFORE DEPLOYMENT
    Route::get('/cleartable/all', [DatabaseTestController::class, 'clearAllTables']);
    Route::get('/cleartable/{tableName}', [DatabaseTestController::class, 'clearTable']);
    Route::get('/randomfill/{tableName}/{amount}', [DatabaseTestController::class, 'randomFillTable']);
    Route::get('/simulatedfill/{et}/{inf}/{mcd}/{wi}', [DatabaseTestController::class, 'simulatedFillStudents']);

    Route::get('/randassigntimeslots/{timeslotsAmount}', [DatabaseTestController::class, 'randomAssignTimeslots']);
    // Only works with exactly 3 timeslots and only on ET Students
    Route::get('/simassigntimeslots/{amount1}/{amount2}/{amount3}', [DatabaseTestController::class, 'simulatedAssignTimeslots']);

    Route::get('/students/{attr}/{val?}/{val2?}', [DatabaseTestController::class, 'getStudentsBy']);
    Route::get('/tutors/{attr}/{val?}', [DatabaseTestController::class, 'getTutorsBy']);

    Route::get('/resetassign', [AdminController::class, 'resetGroupAssignment']);
    Route::get('/assign/{groupSize}/groupphase', [AdminController::class, 'randAssignmentGroupPhase']);
    Route::get('/assign/{groupSize}/fhtour/{course}', [AdminController::class, 'randAssignmentFhTour']);
});
