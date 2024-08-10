<?php

namespace App\Http\Controllers;

use App\Helpers\StatsHelper;
use App\Models\BallInPlay;
use App\Models\Person;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StatsController extends Controller
{
    public function show(Request $request) {
        $players = [];
        switch (true) {
            case $request->input('seasons'):
                $teams = Team::whereIn('season', $request->input('seasons'))->get();
                foreach ($teams as $team) {
                    foreach ($team->players as $player) {
                        $id = $player->person->id;
                        if (!isset($players[$id])) {
                            $players[$id] = new StatsHelper([]);
                        }
                        $players[$id]->merge($player->stats);
                    }
                }
                break;
            default:
                return response(400);
        }

        $totals = new StatsHelper([]);
        foreach ($players as &$player) {
            $totals->merge($player);
            $player->derive();
        }
        $totals->derive();
        return view('team.show', [
            'team' => $team,
            'stats' => $players,
            'totals' => $totals,
            'people' => Person::whereIn('id', array_keys($players))->get(),
            'ballsInPlay' => BallInPlay::whereRelation('player', 'team_id', $team->id)->get(),
        ]);
    }

    public function addPlayer(Team $team, Request $request) {
        $query = $request->query();
        $person = new Person($query);
        $person->save();
        $player = new Player($query);
        $player->team()->associate($team);
        $player->person()->associate($person);
        $player->save();
        return new JsonResponse(['status' => 'success', 'created' => $player->id]);
    }
}