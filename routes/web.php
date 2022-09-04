<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardEventController;
use App\Http\Controllers\DatabaseTestController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RedirectIfAuthenticated;
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
Route::get('/', [AppController::class, 'index'])->name('app.index');

Route::group([
    'middleware' => [
        RedirectIfAuthenticated::class
    ],
], function () {
    Route::get('/login', [AppController::class, 'login'])->name('app.login');
    Route::post('/login', [AppController::class, 'loginUser'])->name('app.loginUser');
    Route::get('/register', [AppController::class, 'register'])->name('app.register');
    Route::post('/register', [AppController::class, 'registerUser'])->name('app.registerUser');
});

Route::group([
    'prefix' => 'dashboard',
    'middleware' => [
        Authenticate::class,
    ],
], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');

    Route::group([
        'prefix' => 'event/{event}',
    ], function () {
        Route::get('/', [DashboardEventController::class, 'index'])->name('dashboard.event.index');
        Route::get('/register', [DashboardEventController::class, 'register'])->name('dashboard.event.register');
        Route::post('/register', [DashboardEventController::class, 'registerUser'])->name('dashboard.event.registerUser');
        Route::get('/unregister', [DashboardEventController::class, 'unregister'])->name('dashboard.event.unregister');
        Route::post('/unregister', [DashboardEventController::class, 'unregisterUser'])->name('dashboard.event.unregisterUser');
    });

    Route::get('{slug?}', [DashboardController::class, 'cmsPage'])->where('slug', '.*');
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

Route::get('{any?}', [AppController::class, 'notFound'])->where('any', '.*');
