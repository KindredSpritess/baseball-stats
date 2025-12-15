<?php

namespace App\Casts;

use App\Models\Game;
use App\Models\Player;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class GameState implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  Game  $game
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($game, string $key, $value, array $attributes)
    {
        $value = json_decode($value ?? '{}', true);
        $game->inning = $value['inning'] ?? 1;
        $game->half = $value['half'] ?? 0;

        $game->balls = $value['balls'] ?? 0;
        $game->strikes = $value['strikes'] ?? 0;
        $game->outs = $value['outs'] ?? 0;
        $game->expectedOuts = $value['expectedOuts'] ?? 0;

        $game->score = $value['score'] ?? [0, 0];
        $game->linescore = $value['linescore'] ?? [[0], []];
        $game->atBat = $value['atBat'] ?? [0, 0];
        $game->ended = $value['ended'] ?? false;

        $players = [];
        $decodeArray = function ($team, &$out, $in) use (&$players, &$decodeArray) {
            foreach ($in as $key => $value) {
                if (is_array($value)) {
                    $decodeArray($team, $out[$key], $value);
                } else if ($value) {
                    if (!isset($players[$value])) {
                        $players[$value] = $team->players()->find($value);
                    }
                    $out[$key] = $players[$value];
                }
            }
        };

        $repairLineups = function (&$lineup) {
            foreach ($lineup as $key => $spot) {
                // If it's a Player, wrap it in an array
                if (is_a($spot, Player::class)) {
                    $lineup[$key] = [$spot];
                }
            }
        };

        $decodeArray($game->half ? $game->home_team : $game->away_team, $game->bases, $value['bases'] ?? []);
        $decodeArray($game->away_team, $game->defense[0], $value['defense'][0] ?? []);
        $decodeArray($game->home_team, $game->defense[1], $value['defense'][1] ?? []);
        $decodeArray($game->away_team, $game->lineup[0], $value['lineup'][0] ?? []);
        $decodeArray($game->home_team, $game->lineup[1], $value['lineup'][1] ?? []);
        $decodeArray($game->away_team, $game->pitchers[0], $value['pitchers'][0] ?? []);
        $decodeArray($game->home_team, $game->pitchers[1], $value['pitchers'][1] ?? []);
        $repairLineups($game->lineup[0]);
        $repairLineups($game->lineup[1]);
        $game->runners = array_map(function ($r) use ($game) {
            $team = $game->half ? $game->away_team : $game->home_team;
            return [
                'pitcher' => $team->players()->find($r['pitcher']),
                'base' => $r['base'],
                'earned' => $r['earned'],
                'expectedOuts' => $r['expectedOuts'],
                'origin' => $r['origin'] ?? null,
            ];
        }, $value['runners'] ?? []);
    
        return "decoded";
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  Game  $game
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($game, string $key, $value, array $attributes): string
    {
        $getId = fn(Player $player) => $player->id;
        $out = [
            'inning' => $game->inning,
            'half' => $game->half,
            'balls' => $game->balls,
            'strikes' => $game->strikes,
            'expectedOuts' => $game->expectedOuts,
            'outs' => $game->outs,
            'score' => $game->score,
            'linescore' => $game->linescore,
            'atBat' => $game->atBat,
            'ended' => $game->ended,
            'bases' => array_map(fn($p) => ($p ? $p->id : null), $game->bases),
            'runners' => array_map(function ($r) use ($getId) {
                return [
                    'pitcher' => $getId($r['pitcher']),
                    'base' => $r['base'],
                    'earned' => $r['earned'],
                    'expectedOuts' => $r['expectedOuts'],
                    'origin' => $r['origin'] ?? null,
                ];
            }, $game->runners),
            'defense' => array_map(function ($d) use ($getId) { return array_map($getId, $d); }, $game->defense),
            'lineup' => array_map(
                fn ($l) => array_map(fn ($s) => array_map($getId, $s), $l),
                $game->lineup
            ),
            'pitchers' => array_map(function ($p) use ($getId) { return array_map($getId, $p); }, $game->pitchers),
        ];
        return json_encode($out);
    }
}
