<?php

namespace App\Http\Controllers;

use App\Helpers\StatsHelper;
use App\Models\BallInPlay;
use App\Models\Person;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
        $teams = Player::where('person_id', $person->id)->select('team_id')->distinct()->get()->pluck('team_id');
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
            $totals->merge($stats[$player->game->id]);
            $stats[$player->game->id]->derive();
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

    public function inPlays(Person $person, Team $team, $position) {
        $posIndex = $position - 1;
        $player_ids = $team->players()->where('person_id', $person->id)->pluck('id')->join(',');
        $balls = BallInPlay::whereRaw("JSON_EXTRACT(fielders, '$[$posIndex]') IN ($player_ids)")->get();

        return response(view('components.field', [
            'ballsInPlay' => $balls,
        ]))->header('Content-Type', 'image/svg+xml');
    }

    /**
     * Search for players by name, prioritizing those who have played on the specified team
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query');
        $teamId = $request->input('team_id');

        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }

        // Get all persons matching the query
        $persons = Person::where('firstName', 'like', "%{$query}%")
            ->orWhere('lastName', 'like', "%{$query}%")
            ->get();

        // If a team ID is provided, mark persons who have played for this team
        if ($teamId) {
            $teamPlayerIds = Player::where('team_id', $teamId)
                ->pluck('person_id')
                ->toArray();

            $persons = $persons->map(function ($person) use ($teamPlayerIds) {
                $person->played_for_team = in_array($person->id, $teamPlayerIds);
                return $person;
            });

            // Sort by whether they've played for the team before (prioritizing those who have)
            $persons = $persons->sortByDesc('played_for_team')->values();
        }

        return response()->json($persons);
    }

    public function teamPlayers(Team $team): JsonResponse
    {
        // Extract game id from the referrer.
        $referrer = request()->headers->get('referer');
        $matches = [];
        preg_match('/\/game\/(\d+)/', $referrer, $matches);
        $players = Player::where('team_id', $team->id)
            // Exclude players already in the lineup for the game
            ->when(isset($matches[1]), function ($query) use ($matches) {
                $query->whereNotIn('person_id', function ($subquery) use ($matches) {
                    $subquery->select('person_id')
                        ->from('players')
                        ->where('game_id', $matches[1]);
                });
            })
            ->with('person')
            ->get()
            ->groupBy(fn ($player) => "{$player->person->lastName}, {$player->person->firstName}")
            ->map(fn ($group) => [
                'number' => $group->filter(fn ($player) => $player->number)->mode('number'),
                'person' => $group->first()->person
            ]);

        return response()->json($players);
    }
}
