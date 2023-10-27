<?php

use App\Casts\GameState;
use App\Http\Controllers\GameController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\TeamController;
use App\Models\Game;
use App\Models\Team;
use Illuminate\Support\Facades\Log;
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

Route::get('/', function () {
    $seasons = Team::select('season')->distinct()->get()->pluck('season');
    return view('welcome', ['seasons' => $seasons,
                            'games' => Game::orderBy('firstPitch')->get(),
                            'teams' => Team::all()]);
});

Route::controller(GameController::class)->group(function() {
    Route::get('/game/create', 'create');
    Route::post('/game/store', 'store')->name('gamestore');
    Route::get('/game/{game}', 'show')->name('game');
    Route::get('/game/view/{game}', 'view')->name('game.view');
    Route::put('/game/{game}/log', 'play')->name('gamelog');
});

Route::controller(TeamController::class)->group(function () {
    Route::get('/team/create', 'create');
    Route::post('/team/create', 'store')->name('teamstore');
    Route::get('/team/{team}', 'show')->name('team');
});

Route::controller(PersonController::class)->group(function () {
    Route::get('/person/{person}', 'show')->name('person.show');
    Route::get('/person/{person}/{team}', 'teamGames')->name('person.games');
});