<?php

namespace App\Http\Controllers;

use App\Helpers\StatsHelper;
use App\Models\BallInPlay;
use App\Models\Person;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function create() {
        return view('team.create', [
            'seasons' => Season::all(),
        ]);
    }

    public function store(Request $request) {
        $team = new Team($request->validate([
            'name' => 'required|string|max:100',
            'short_name' => 'required|string|max:50',
            'season_id' => 'required|exists:seasons,id',
            'primary_color' => 'nullable|hex_color',
            'secondary_color' => 'nullable|hex_color',
        ]));
        $team->save();
        return redirect()->route('team', ['team' => $team->id]);
    }

    public function show(Request $request, Team $team) {
        $players = collect();
        $player_ids = [];
        foreach ($team->players as $player) {
            $id = $player->person->id;
            $player_ids[] = $player->id;
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
        $people = Player::where('team_id', $team->id)->select('person_id')->distinct()->get()->pluck('person_id');
        $qualified = $totals->GS && $request->query('qualified') !== 'all';

        $player_ids = implode(',', $player_ids);
        if ($player_ids) {
            $pitcherBalls = BallInPlay::whereRaw("JSON_EXTRACT(fielders, '$[0]') IN ($player_ids)")->get()->groupBy(fn($ball) => $ball->pitcher[0]->person_id);
        } else {
            $pitcherBalls = collect();
        }

        return view('team.show', [
            'team' => $team,
            'stats' => $players,
            'totals' => $totals,
            'people' => Person::whereIn('id', $people)->get(),
            'ballsInPlay' => BallInPlay::whereRelation('player', 'team_id', $team->id)->get()->groupBy('player.person_id'),
            'pitchingBIP' => $pitcherBalls,
            'minPA' => $qualified ? $team->games()->count() * ($totals->PA / $totals->GS - 1) : 0,
            'minIP' => $qualified ? $team->games()->count() / 3 : 0,
            'minFI' => $qualified ? $totals->FI / 9 / 2 : 0,
        ]);
    }

    public function edit(Team $team) {
        return view('team.edit', [
            'team' => $team,
            'seasons' => Season::all(),
        ]);
    }

    public function update(Request $request, Team $team) {
        $team->fill($request->validate([
            'name' => 'required|string|max:100',
            'short_name' => 'required|string|max:50',
            'season_id' => 'required|exists:seasons,id',
            'primary_color' => 'nullable|hex_color',
            'secondary_color' => 'nullable|hex_color',
        ]));
        $team->save();
        return redirect()->route('team', ['team' => $team->id]);
    }
}