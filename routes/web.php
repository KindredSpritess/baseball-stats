<?php

use App\Casts\GameState;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TeamController;
use App\Models\Game;
use App\Models\Redirect;
use App\Models\Team;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/auth/{provider}', [AuthController::class, 'redirectToProvider'])->name('auth.redirect');
Route::get('/auth/{provider}/callback', [AuthController::class, 'handleProviderCallback'])->name('auth.callback');

Route::get('/login', function () {
    return view('login');
})->name('login');

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
    return view('welcome', [
        'seasons' => $seasons,
        'games' => Game::orderBy('firstPitch')->get(),
        'teams' => Team::all()
    ]);
});

Route::controller(GameController::class)->group(function() {
    Route::get('/game/create', 'create')->name('game.create')->middleware('can:create-game');
    Route::post('/game/store', 'store')->name('gamestore')->middleware('can:create-game');
    Route::get('/game/{game}', 'show')->name('game')->middleware('can:score-game,game');
    Route::get('/game/view/{game}', 'view')->name('game.view');
    Route::put('/game/{game}/log', 'play')->name('gamelog')->middleware('can:score-game,game');
    Route::patch('/game/{game}/log', 'plays')->name('fullgamelog')->middleware('can:score-game,game');
    Route::get('/game/{game}/boxscore', 'boxscore')->name('game.boxscore');
});

Route::controller(TeamController::class)->group(function () {
    Route::get('/team/create', 'create')->name('team.create')->middleware('can:create-team');
    Route::post('/team/create', 'store')->name('teamstore')->middleware('can:create-team');
    Route::get('/team/{team}', 'show')->name('team');
});

Route::controller(PersonController::class)->group(function () {
    Route::get('/person/{person}', 'show')->name('person.show');
    Route::get('/person/{person}/{team}', 'teamGames')->name('person.games');
    Route::get('/person/{person}/{team}/inplays/{position}', 'inplays')->name('person.inplays');
});

Route::controller(StatsController::class)->group(function () {
    Route::get('/stats', 'show')->name('stats.show');
});

Route::get('/volunteer-form/{redirect:key}', function(Redirect $redirect) {
    return redirect($redirect->destination);
})->name('volunteer');

Route::get('/schedule', function() {
    return view('schedules', [
        'calendars' => glob(public_path('schedules/*')),
    ]);
})->name('schedules');