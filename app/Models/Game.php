<?php

namespace App\Models;

use App\Casts\GameState;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon $firstPitch
 * @property int $away
 * @property int $home
 * @property string $location
 * @property int|null $duration
 * @property mixed|null $state
 * @property int $locked
 * @property int $ended
 * @property array<array-key, mixed>|null $dimensions
 * @property-read \App\Models\Team|null $away_team
 * @property-read \App\Models\Team|null $home_team
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Player> $players
 * @property-read int|null $players_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Play> $plays
 * @property-read int|null $plays_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game whereAway($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game whereDimensions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game whereEnded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game whereFirstPitch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game whereHome($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game whereLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Game extends Model
{
    use HasFactory;

    public int $inning = 1;
    public int $half = 0;

    public int $balls = 0;
    public int $strikes = 0;
    public int $outs = 0;
    public int $expectedOuts = 0;
    // public bool $ended = false;

    public array $atBat = [0, 0];
    public array $score = [0, 0];

    public array $linescore = [[0], []];

    public array $bases = [null, null, null];
    public array $runners = [];

    public array $defense = [[], []];
    public array $lineup = [[], []];

    public array $pitchers = [[], []];

    public int $lob = 0;

    public array $pitchersOfRecord = [
        'winning' => null,
        'losing' => null,
        'saving' => null,
    ];

    public Collection $ballsInPlay;

    protected $fillable = ['location', 'firstPitch', 'duration', 'dimensions'];

    protected $casts = [
        'state' => GameState::class,
        'dimensions' => 'array',
        'firstPitch' => 'datetime',
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

    public function scorer() {
        return $this->belongsTo(User::class, 'scorer_id', 'id');
    }

    public function substitute(int $home, Player $player, ?Player $replacing = null, ?string $fieldPos = null) : void {
        $lineup =& $this->lineup[$home];
        if ($replacing) {
            foreach ($lineup as &$spot) {
                if (in_array($replacing, $spot, true)) {
                    $spot[] = $player;
                    break;
                }
            }
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
            $lineup[] = [$player];
        }

        if (!$fieldPos) {
            return;
        }

        // If the player is already at the position, do nothing.
        if (isset($this->defense[$home][$fieldPos]) && $this->defense[$home][$fieldPos]->is($player)) {
            return;
        }

        // Remove player from previous position.
        foreach ($this->defense[$home] as $pos => $p) {
            if ($p->is($player)) {
                unset($this->defense[$home][$pos]);
                break;
            }
        }
        $this->defense[$home][$fieldPos] = $player;
        if ($fieldPos === '1') {
            $player->evt('GP');
            $this->expectedOuts = $this->outs;
            $this->pitchers[$home][] = $player;
            // Add inherited runners.
            foreach ($this->bases as $runner) {
                if ($runner) $this->pitching()->evt('IR');
            }
            // Check for save situation.
            $lead = $this->score[$home] - $this->score[($home+1)%2];
            if (($lead > 0 && $lead <= 3) || ($lead > 0 && $lead <= count(array_filter($this->bases)) + 2)) {
                $this->pitchersOfRecord['saving'] = $player;
            }
        }
    }

    public function sideAway() {
        $this->lob = count(array_filter($this->bases));
        $this->outs = 0;
        $this->expectedOuts = 0;
        $this->balls = 0;
        $this->strikes = 0;
        $this->inning += $this->half;
        $this->half = ($this->half + 1) % 2;
        $this->bases = [null, null, null];
        $this->runners = [];
        $this->linescore[$this->half][] = 0;
    }

    public function advanceRunner(Player $player,
                                  float $bases,
                                  bool $earned = true,
                                  bool $decisiveError = false,
                                  ?string $origin = null,
                                  bool $replaces = false) {
        if (!isset($this->runners[$player->id])) {
            $this->runners[$player->id] = [
                'pitcher' => $this->pitching(),
                'base' => 0,
                'earned' => ($decisiveError || $this->expectedOuts > 2) ? -100000000000 : 0,
                'expectedOuts' => (int)$this->expectedOuts,
                'origin' => $origin ?? null,
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
            $player->evt("R.{$runner['origin']}");
            $runner['pitcher']->evt('RA');
            $runner['pitcher']->evt("RA.{$runner['origin']}");
            $runner['base'] = -100000000000;
            if ($this->score[0] == $this->score[1]) {
                // Losing pitcher should be the one who allowed the run on base.
                $this->pitchersOfRecord['losing'] = $runner['pitcher'];
            }
            if ($runner['earned'] < 0) {
                unset($this->runners[$player->id]);
            }
            if ($this->pitching()->isNot($runner['pitcher'])) {
                // Inherited runner scored.
                $this->pitching()->evt('IRS');
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

    public function scores(): void {
        $this->score[$this->half]++;
        $this->linescore[$this->half][$this->inning - 1]++;
        // Update winning and losing pitchers.
        $lead = $this->score[$this->half] - $this->score[($this->half+1)%2];
        if ($lead === 1) {
            // $this->pitchersOfRecord['losing'] = $this->pitching();
            $this->pitchersOfRecord['winning'] = $this->defense[$this->half]['1'];
        } else if ($lead === 0) {
            $this->pitchersOfRecord['winning'] = null;
            $this->pitchersOfRecord['losing'] = null;
            $this->pitchersOfRecord['saving'] = null;
        }
    }

    public function pitching() : Player {
        return $this->defense[($this->half+1)%2]['1'];
    }

    public function hitting() : ?Player {
        $spot = $this->lineup[$this->half][$this->atBat[$this->half]] ?? null;
        if ($spot) {
            return end($spot);
        }
        return null;
    }

    public function fielding(string $pos): ?Player {
        return $this->defense[($this->half+1)%2][$pos] ?? null;
    }

    public function plays() {
        return $this->hasMany(Play::class);
    }
}
