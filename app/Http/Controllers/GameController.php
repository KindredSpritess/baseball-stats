<?php

namespace App\Http\Controllers;

use App\Casts\GameState;
use App\Models\Game;
use App\Models\Play;
use App\Models\Team;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GameController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('game.create', [
            'teams' => Team::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $game = new Game();
        $game->home_team()->associate(Team::find($request->input('home')));
        $game->away_team()->associate(Team::find($request->input('away')));
        $game->fill($request->input());
        $game->save();
        return new JsonResponse(['status' => 'success', 'created' => $game->id]);
    }

    public function play(Game $game, Request $request) {
        if ($game->locked) {
            throw new Exception('Cannot update locked game.');
        }
        $game->state === 'decoded';
        if ($request->header('Content-Type') === 'text/plain') {
            $play = new Play(['play' => $request->getContent()]);
            $json = true;
        } else {
            $play = new Play(['play' => $request->input('play')]);
            $json = $request->accepts('application/json');
        }
        $play->apply($game);
        $game->state = 'force encode';
        $game->plays()->save($play);
        $game->push();
        foreach ($game->lineup as $lineup) {
            foreach ($lineup as $player) {
                $player->save();
            }
        }
        if ($json) {
            $gs = new GameState;
            return new JsonResponse(['status' => 'success', 'state' => $gs->set($game, '', '', [])]);
        } else {
            return redirect()->route('game', ['game' => $game->id]);
        }
    }

    public function plays(Game $game, Request $request) {
        if ($game->locked) {
            throw new Exception('Cannot update locked game.');
        }
        if ($request->header('Content-Type') === 'text/plain') {
            $plays = $request->getContent();
        } else {
            $plays = $request->input('plays');
        }
        $game->players()->delete();
        $plays = collect(preg_split("/\n/", $plays))
            ->filter(fn (string $play) => trim($play))
            ->map(fn (string $play) => new Play(['play' => $play]));
        $gs = new GameState;
        $gs->get($game, 'state', '{}', []);
        foreach ($plays as $k => $play) {
            try {
                $play->apply($game);
            } catch (\Exception $e) {
                Log::error("Error with line $k: {$play->play}");
                throw $e;
            }
        }
        $game->state = 'force encode';
        $game->plays()->delete();
        $game->plays()->saveMany($plays);
        $game->push();
        foreach ($game->lineup as $lineup) {
            foreach ($lineup as $player) {
                $player->save();
            }
        }
        $gs = new GameState;
        return new JsonResponse(['status' => 'success', 'state' => json_decode($gs->set($game, '', '', []), true)]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Game  $game
     * @return \Illuminate\Http\Response
     */
    public function show(Game $game)
    {
        // Force load.
        $state = $game->state;
        return view('game.show', ['game' => $game]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Game  $game
     * @return \Illuminate\Http\Response
     */
    public function view(Game $game)
    {
        // Force load.
        $state = $game->state;
        $game->locked = true;
        return view('game.show', ['game' => $game]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Game  $game
     * @return \Illuminate\Http\Response
     */
    public function edit(Game $game)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Game  $game
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Game $game)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Game  $game
     * @return \Illuminate\Http\Response
     */
    public function destroy(Game $game)
    {
        //
    }
}
