<?php

namespace App\Http\Controllers;

use App\Helpers\StatsHelper;
use App\Models\Person;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    public function create() {
        return view('team.create');
    }

    public function store(Request $request) {
        $team = new Team($request->input());
        $team->save();
        return redirect()->route('team', ['team' => $team->id]);
    }

    public function show(Team $team) {
        $players = [];
        foreach ($team->players as $player) {
            $id = $player->person->id;
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
        $people = Player::where('team_id', $team->id)->select('person_id')->distinct()->get();
        return view('team.show', [
            'team' => $team,
            'stats' => $players,
            'totals' => $totals,
            'people' => Person::whereIn('id', $people)->get(),
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