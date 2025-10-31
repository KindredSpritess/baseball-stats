<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\TeamController;
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

// Route::controller(TeamController::class)->group(function() {
//     Route::put('/team', 'create');
// });

Route::controller(GameController::class)->group(function() {
    Route::get('/game/{game}', 'get');
    // Route::post('/game/store', 'store');
    // Route::put('/game/{game}/log', 'play')->name('gamelog');
    // Route::patch('/game/{game}/log', 'plays')->name('fullgamelog');
});

Route::controller(\App\Http\Controllers\PersonController::class)->group(function() {
    Route::get('/players/search', 'search');
    Route::get('/players/team/{team}', 'teamPlayers');
});
