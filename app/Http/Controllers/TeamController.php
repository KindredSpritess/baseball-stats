<?php

namespace App\Http\Controllers;

use App\Helpers\StatsHelper;
use App\Models\BallInPlay;
use App\Models\Person;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function show(Request $request, Team $team) {
        $players = collect();
        foreach ($team->players as $player) {
            $id = $player->person->id;
            if (!$players->has($id)) {
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
        $qualified = $totals->GS && $request->query('qualified') !== 'all';
        return view('team.show', [
            'team' => $team,
            'stats' => $players,
            'totals' => $totals,
            'people' => Person::whereIn('id', $people)->get(),
            'ballsInPlay' => BallInPlay::whereRelation('player', 'team_id', $team->id)->get()->groupBy('player.person_id'),
            'minPA' => $qualified ? $team->games()->count() * ($totals->PA / $totals->GS - 1) : 0,
            'minIP' => $qualified ? $team->games()->count() / 3 : 0,
            'minFI' => $qualified ? $totals->FI / 9 / 2 : 0,
        ]);
    }
}