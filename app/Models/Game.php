<?php

namespace App\Models;

use App\Casts\GameState;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    public int $inning = 1;
    public int $half = 0;

    public int $balls = 0;
    public int $strikes = 0;
    public int $outs = 0;
    public int $expectedOuts = 0;

    public array $atBat = [0, 0];
    public array $score = [0, 0];

    public array $bases = [null, null, null];
    public array $runners = [];

    public array $defense = [[], []];
    public array $lineup = [[], []];

    public Collection $ballsInPlay;

    protected $fillable = ['location', 'firstPitch', 'duration', 'dimensions'];

    protected $casts = [
        'state' => GameState::class,
        'dimensions' => 'array',
    ];

    public function home_team() {
        return $this->belongsTo(Team::class, 'home', 'id');
    }

    public function away_team() {
        return $this->belongsTo(Team::class, 'away', 'id');
    }

    public function players() {
        return $this->hasMany(Player::class);
    }

    public function substitute(int $home, Player $player, ?Player $replacing = null, ?string $fieldPos = null) : void {
        if (!$fieldPos) {
            return;
        }

        $this->defense[$home][$fieldPos] = $player;
        if ($fieldPos === '1') {
            $player->evt('GP');
            $this->expectedOuts = $this->outs;
        }

        $lineup =& $this->lineup[$home];
        if ($replacing) {
            $key = array_search($replacing, $lineup, true);
            $lineup[$key] = $player;
            foreach ($this->bases as $k => $p) {
                if ($p === $replacing) {
                    $this->bases[$k] = $player;
                    break;
                }
            }
            if (isset($this->runners[$replacing->id])) {
                $this->runners[$player->id] = $this->runners[$replacing->id];
                unset($this->runners[$replacing->id]);
            }
        } else if ($fieldPos !== '1' || !isset($this->defense[$home]['DH'])) {
            $lineup[] = $player;
        }
    }

    public function sideAway() {
        $this->outs = 0;
        $this->expectedOuts = 0;
        $this->balls = 0;
        $this->strikes = 0;
        $this->inning += $this->half;
        $this->half = ($this->half + 1) % 2;
        $this->bases = [null, null, null];
        $this->runners = [];
    }

    public function advanceRunner(Player $player,
                                  float $bases,
                                  bool $earned = true,
                                  bool $decisiveError = false,
                                  bool $replaces = false) {
        if (!isset($this->runners[$player->id])) {
            $this->runners[$player->id] = [
                'pitcher' => $this->pitching(),
                'base' => 0,
                'earned' => ($decisiveError || $this->expectedOuts > 2) ? -100000000000 : 0,
                'expectedOuts' => (int)$this->expectedOuts,
            ];
        }

        if ($decisiveError) {
            foreach ($this->runners as &$runner) {
                $runner['expectedOuts']++;
                if ($runner['expectedOuts'] > 2) $runner['earned'] = -100000000000;
            }
            $this->expectedOuts++;
        }

        $runner = &$this->runners[$player->id];
        $runner['base'] += $bases;
        if ($earned) $runner['earned'] += $bases;
        if ($decisiveError) $runner['earned'] = -100000000000;

        $keys = array_reverse(array_keys($this->runners));
        $fb = $runner['earned'];
        for ($i = array_search($player->id, $keys) + 1; $i < count($keys); $i++) {
            if ($fb < 0) break;
            $k = $keys[$i];
            if ($this->runners[$k]['earned'] < 0) continue;
            if ($this->runners[$k]['earned'] > 3) continue;
            if ($this->runners[$k]['earned'] <= $fb) {
                $this->runners[$k]['earned'] = ++$fb;
                if ($this->runners[$k]['earned'] >= 4 && $this->runners[$k]['expectedOuts'] < 3) {
                    $this->runners[$k]['pitcher']->evt('ER');
                    unset($this->runners[$k]);
                }
            }
        }

        if ($runner['earned'] >= 4) {
            $runner['pitcher']->evt('ER');
            unset($this->runners[$player->id]);
        }
        if ($runner['base'] >= 4) {
            $runner['pitcher']->evt('RA');
            $runner['base'] = -100000000000;
            if ($runner['earned'] < 0) {
                unset($this->runners[$player->id]);
            }
        }
    }

    public function out() {
        $this->outs++;
        $this->expectedOuts++;
        foreach ($this->runners as &$runner) {
            $runner['expectedOuts']++;
            if ($runner['expectedOuts'] > 2) $runner['earned'] = -100000000000;
        }
        $this->pitching()->evt('TO');
        foreach ($this->defense[($this->half+1)%2] as $pos => $fielder) {
            if (intval($pos)) {
                $fielder->evt('DO');
                $fielder->evt("DO.$pos");
            }
        }
    }

    public function batterUp() {
        throw_unless(count($this->bases) === 3);
        $this->balls = 0;
        $this->strikes = 0;
        $this->atBat[$this->half] += 1;
        $this->atBat[$this->half] %= count($this->lineup[$this->half]);
    }

    public function pitching() : Player {
        return $this->defense[($this->half+1)%2]['1'];
    }

    public function hitting() : ?Player {
        return $this->lineup[$this->half][$this->atBat[$this->half]] ?? null;
    }

    public function fielding(string $pos): ?Player {
        return $this->defense[($this->half+1)%2][$pos] ?? null;
    }

    public function plays() {
        return $this->hasMany(Play::class);
    }
}
