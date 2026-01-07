<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\SeasonController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->put('/user/preferences', [UserController::class, 'updatePreferences']);

Route::middleware('auth:sanctum')
    ->get('/season/{season}/preferences', [SeasonController::class, 'getPreferences'])
    ->name('api.season.preferences');
Route::middleware('auth:sanctum')
    ->put('/season/{season}/preferences', [SeasonController::class, 'storePreferences'])
    ->name('api.season.preferences.update');

// Route::controller(TeamController::class)->group(function() {
//     Route::put('/team', 'create');
// });

Route::controller(GameController::class)->group(function() {
    Route::get('/game/{game}', 'get')->name('api.game.get');
    Route::get('/game/{game}/preferences', 'getPreferences')->name('api.game.preferences')->middleware('auth:sanctum');
    // Route::post('/game/store', 'store');
    // Route::put('/game/{game}/log', 'play')->name('gamelog');
    // Route::patch('/game/{game}/log', 'plays')->name('fullgamelog');
});

Route::controller(PersonController::class)->group(function() {
    Route::get('/players/search', 'search')->can('score');
    Route::get('/players/team/{team}', 'teamPlayers')->can('score');
});
