<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\TutorController;
use App\Http\Controllers\TutorGroupController;
use App\Http\Controllers\TutorStationController;
use App\Http\Controllers\AdminController;
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

// student
Route::get('/', [AppController::class, 'index']);
Route::post('/', [AppController::class, 'store']);
Route::group([
    'middleware' => ['isStudent']
], function () {
    Route::get('/group/', [AppController::class, 'group'])->name('group');
});

// tutor
Route::get('/tutor/', [TutorController::class, 'index']);
Route::post('/tutor/', [TutorController::class, 'login']);
Route::group([
    'prefix' => 'tutor',
    'middleware' => ['isTutor']
], function () {
    Route::get('/overview', [TutorController::class, 'overview'])->name('tutor.overview');

    Route::group([
        'prefix' => 'group',
    ], function () {
        Route::get('/{id}', [TutorGroupController::class, 'index']);
        Route::post('/{id}/join', [TutorGroupController::class, 'join']);
        Route::post('/student/{id}/attended', [TutorGroupController::class, 'studentAttended']);
        Route::post('/student/{id}/unattended', [TutorGroupController::class, 'studentUnattended']);
        Route::post('/station/{id}/done', [TutorGroupController::class, 'stationDone']);
        Route::post('/station/{id}/undone', [TutorGroupController::class, 'stationUndone']);
    });

    Route::group([
        'prefix' => 'station',
    ], function () {
        Route::get('/{id}', [TutorStationController::class, 'index']);
        Route::post('/{id}/join', [TutorStationController::class, 'join']);
    });
});
Route::group([
    'prefix' => 'admin',
    'middleware' => ['isTutor', 'isAdmin']
], function () {
    Route::get('/', [AdminController::class, 'index']);
    Route::get('/start', [AdminController::class, 'start']);
    Route::post('/start', [AdminController::class, 'startGrouping']);
    Route::get('/result', [AdminController::class, 'result'])->name('admin.result');
});

Route::group([
    'prefix' => 'dev',
    'middleware' => ['isTutor', 'isAdmin']
], function () {

    //Routes to test factories. REMOVE BEFORE DEPLOYMENT
    Route::get('/testfactorystudent', function () {
        return \App\Models\Student::factory()->make();
    });
    Route::get('/testfactorytutor', function () {
        return \App\Models\Tutor::factory()->make();
    });
    Route::get('/testfactorygroup', function () {
        return \App\Models\Group::factory()->make();
    });
    Route::get('/testfactorystation', function () {
        return \App\Models\Station::factory()->make();
    });
    Route::get('/testfactorytimeslot', function () {
        return \App\Models\Timeslot::factory()->make();
    });

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
