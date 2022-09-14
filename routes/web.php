<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardEventController;
use App\Http\Controllers\DashboardTutorController;
use App\Http\Controllers\DatabaseTestController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\IsLoggedInTutor;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\RedirectIfTutor;
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
    Route::group([
        'middleware' => [
            RedirectIfTutor::class
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
    });

    Route::post('/login-tutor', [DashboardController::class, 'loginTutor'])->name('dashboard.loginTutor');
    Route::group([
        'middleware' => [
            IsLoggedInTutor::class
        ],
    ], function () {
        Route::get('/tutor', [DashboardTutorController::class, 'index'])->name('dashboard.tutor.index');
        Route::get('/tutor/event/{event}', [DashboardTutorController::class, 'event'])->name('dashboard.tutor.event.index');
        Route::get('/tutor/event/{event}/registrations', [DashboardTutorController::class, 'registrations'])->name('dashboard.tutor.event.registrations');
        Route::get('/tutor/slot/{slot}', [DashboardTutorController::class, 'slot'])->name('dashboard.tutor.slot.index');
        Route::get('/tutor/group/{group}', [DashboardTutorController::class, 'group'])->name('dashboard.tutor.group.index');
    });

    Route::get('{slug?}', [DashboardController::class, 'cmsPage'])->where('slug', '.*');
});

// api routes with authentication
Route::group([
    'prefix' => 'api',
], function () {
    Route::get('/registrations/{registration}', [ApiController::class, 'registrationsShow'])->name('api.registrations.show');
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

Route::get('{any?}', [AppController::class, 'notFound'])->where('any', '^((?!api).)*');
