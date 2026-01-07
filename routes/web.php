<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\SeasonController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TeamController;
use App\Models\Game;
use App\Models\Redirect;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Support\Facades\Route;

Route::get('/auth/{provider}', [AuthController::class, 'redirectToProvider'])->name('auth.redirect');
Route::get('/auth/{provider}/callback', [AuthController::class, 'handleProviderCallback'])->name('auth.callback');

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/user', function () {
    return view('user.preferences');
})->name('user');

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
    $seasons = Season::all();
    return view('welcome', [
        'seasons' => $seasons,
        'games' => Game::orderBy('firstPitch')->get(),
        'teams' => Team::orderBy('name')->get(),
    ]);
});

Route::get('/cast', function() {
    return view('game.receiver', [
        'applicationId' => env('GOOGLE_CAST_APPLICATION'),
    ]);
})->name('cast');

Route::controller(GameController::class)->group(function() {
    Route::get('/game/create', 'create')->name('game.create')->middleware('can:create-game');
    Route::post('/game/store', 'store')->name('gamestore')->middleware('can:create-game');
    Route::get('/game/{game}', 'show')->name('game')->middleware('can:score-game,game');
    Route::get('/game/{game}/score', 'score')->name('game.score')->middleware('can:score-game,game');
    Route::get('/game/view/{game}', 'view')->name('game.view');
    Route::get('/game/receiver/{game}', 'receiver')->name('game.receiver');
    Route::put('/game/{game}/log', 'play')->name('gamelog')->middleware('can:score-game,game');
    Route::post('/game/{game}/undo', 'undoLastPlay')->name('game.undoLastPlay')->middleware('can:score-game,game');
    Route::patch('/game/{game}/log', 'plays')->name('fullgamelog')->middleware('can:score-game,game');
    Route::get('/game/{game}/boxscore', 'boxscore')->name('game.boxscore');
});

Route::controller(TeamController::class)->group(function () {
    Route::get('/team/create', 'create')->name('team.create')->middleware('can:create-team');
    Route::post('/team/create', 'store')->name('teamstore')->middleware('can:create-team');
    Route::get('/team/{team}', 'show')->name('team');
    Route::get('/team/{team}/edit', 'edit')->name('team.edit')->middleware('can:edit-team,team');
    Route::post('/team/{team}/edit', 'update')->name('team.update')->middleware('can:edit-team,team');
});

Route::controller(PersonController::class)->group(function () {
    Route::get('/person/{person}', 'show')->name('person.show');
    Route::get('/person/{person}/{team}', 'teamGames')->name('person.games');
    Route::get('/person/{person}/{team}/inplays/{position}', 'inplays')->name('person.inplays');
});

Route::controller(StatsController::class)->group(function () {
    Route::get('/stats', 'show')->name('stats.show');
});

Route::controller(SeasonController::class)->group(function () {
    Route::get('/season/{season}/preferences', 'preferences')->name('season.preferences');
});

Route::get('/volunteer-form/{redirect:key}', function(Redirect $redirect) {
    return redirect($redirect->destination);
})->name('volunteer');

Route::get('/schedule', function() {
    return view('schedules', [
        'calendars' => glob(public_path('schedules/*')),
    ]);
})->name('schedules');