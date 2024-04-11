<?php

namespace App\Http\Controllers;

use App\Helpers\StatsHelper;
use App\Models\BallInPlay;
use App\Models\Person;
use App\Models\Player;
use App\Models\Team;

class PersonController extends Controller
{
    public function show(Person $person) {
        $players = [];
        foreach ($person->players as $player) {
            $id = $player->team->id;
            if (!isset($players[$id])) {
                $players[$id] = new StatsHelper([]);
            }
            $players[$id]->merge($player->stats);
        }
        $totals = new StatsHelper([]);
        foreach ($players as &$player) {
            $totals->merge($player);
            $player->derive();
        }
        $totals->derive();
        $teams = Player::where('person_id', $person->id)->select('team_id')->distinct()->get();
        return view('person.show', [
            'person' => $person,
            'stats' => $players,
            'totals' => $totals,
            'teams' => Team::whereIn('id', $teams)->get(),
            'ballsInPlay' => BallInPlay::whereRelation('player', 'person_id', $person->id)->get(),
        ]);
    }

    public function teamGames(Person $person, Team $team) {
        $players = $team->players()->where('person_id', $person->id)->get();
        $stats = [];
        $games = [];
        $totals = new StatsHelper([]);
        foreach ($players as $player) {
            $games[] = $player->game;
            $stats[$player->game->id] = new StatsHelper($player->stats);
            $stats[$player->game->id]->derive();
            $totals->merge($stats[$player->game->id]);
        }
        $totals->derive();
        usort($games, function($a, $b) {
            return $a->firstPitch <=> $b->firstPitch;
        });
        return view('person.games', [
            'person' => $person,
            'team' => $team,
            'games' => $games,
            'stats' => $stats,
            'totals' => $totals,
            'ballsInPlay' => BallInPlay::whereRelation('player', 'person_id', $person->id)->whereRelation('player', 'team_id', $team->id)->get(),
        ]);
    }
}
