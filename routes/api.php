<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\PublicApiController;
use App\Http\Middleware\PublicApiSecret;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/server/ping', [ApiController::class, 'ping'])->name('api.ping');

Route::group([
    'prefix' => 'v1',
    'middleware' => [
        PublicApiSecret::class,
    ],
], function () {
    Route::get('/users', [PublicApiController::class, 'users'])->name('apiV1.users');
});
