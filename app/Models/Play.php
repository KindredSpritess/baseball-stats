<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use NumberFormatter;

class Play extends Model
{
    use HasFactory;

    protected $fillable = ['play'];

    protected static function booted() {
        static::saved(function (self $play) {
            if ($play->tempBallInPlay) {
                $play->tempBallInPlay->play()->associate($play);
                $play->tempBallInPlay->save();
            }
        });
    }

    const POSITIONS = [
        1 => 'P',
        2 => 'C',
        3 => '1B',
        4 => '2B',
        5 => '3B',
        6 => 'SS',
        7 => 'LF',
        8 => 'CF',
        9 => 'RF',
    ];

    const TRAJECTORIES = [
        'G' => 'ground ball',
        'L' => 'line drive',
        'F' => 'fly ball',
        'P' => 'pop up',
        'FF' => 'foul fly',
        'PF' => 'pop foul',
    ];

    const BASES = [
        '0' => '1st',
        '1' => '2nd',
        '2' => '3rd',
    ];

    const HIT = [
        '1' => 'singles',
        '2' => 'doubles',
        '3' => 'triples',
        '4' => 'homers',
    ];

    /** @var ?string */
    private $humanBuffer = null;

    /** @var ?BallInPlay tempBallInPlay */
    private $tempBallInPlay = null;

    public function apply(Game $game) {
        $log = new StringConsumer($this->play);
        // Comment
        if ($log->consume('#')) {
            return;
        }

        if ($log->consume('Side Away')) {
            $game->sideAway();
        }

        // Lineups
        if ($log->consume('@')) {
            $matches = [];
            preg_match('/^([^ ]+) +([^,]+), ([a-zA-Z][a-zA-Z -]*[a-zA-Z]) *(?:#(\d+))?(?:: (.*))?$/', $log, $matches);
            $team = $game->home_team->short_name === $matches[1] ? $game->home_team : $game->away_team;
            $player = new Player();
            $player->team()->associate($team);
            $player->game()->associate($game);
            $person = Person::where('firstName', $matches[3])->where('lastName', $matches[2])->first();
            if (!$person) {
                $person = new Person(['firstName' => $matches[3], 'lastName' => $matches[2]]);
                $person->save();
            }
            $player->person()->associate($person);
            $player->evt('G');
            $player->evt('GS');
            if (isset($matches[4])) {
                $player->number = $matches[4];
            }
            $player->push();
            $game->substitute($game->home_team()->is($team), $player, null, $matches[5]);
            return;
        }

        // Pinch Hitter
        if ($log->consume('PH#')) {
            $game->substitute(!$game->top, Player::find($log), $game->top ? $game->awayLineup[$game->awayAtBat] : $game->homeLineup[$game->homeAtBat]);
            return;
        }

        // Pinch Runner
        if ($log->consume('PR')) {
            $replacing = match ($log[0]) {
                '1' => $game->first,
                '2' => $game->second,
                '3' => $game->third,
            };
            $log->consume('#');
            $game->substitute(!$game->top, Player::find($log), $replacing);
            return;
        }

        // Skip Hitter
        if ($log->consume('-')) {
            $game->batterUp();
            return;
        }

        // Defensive Substitution
        if ($log->consume('DSUB #')) {
            $player = Player::find($log->upto(':'));
            throw_unless($log->consume(': '));
            $position = (string)$log;
            $replacing = $game->top ? $game->homeDefense[$position] : $game->awayDefense[$position];
            $game->substitute($game->top, $player, $replacing, $position);
            return;
        }

        // Defensive swap
        if ($log->consume('DC #')) {
            $player = $game->lineup[($game->half+1)%2][$log->upto(' -> ') - 1];
            throw_unless($log->consume(' -> '));
            $position = (string)$log;
            $game->defense[($game->half+1)%2][$position] = $player;
            $this->log($player->person->lastName . " moves to " . (Play::POSITIONS[$position] ?? $position));
            $this->logA(" {$game->away_team->short_name} {$game->score[0]} to {$game->home_team->short_name} {$game->score[1]}.");
            if ($position == '1') {
                $player->evt('GP');
                $game->expectedOuts = $game->outs;
            }
            return;
        }

        while (!$log->empty()) {
            if ($log->consume('.')) {
                $game->balls = min($game->balls + 1, 3);
                $game->pitching()->evt('Balls');
                $game->hitting()->evt('hBalls');
            } else if ($log->consume('c') || $log->consume('s') || $log->consume('f') || $log->consume('x')) {
                if (($game->balls == 0) && ($game->strikes == 0)) {
                    $game->pitching()->evt('FPS');
                }
                $game->strikes = min($game->strikes + 1, 2);
                $game->pitching()->evt('Strikes');
                $game->hitting()->evt('hStrikes');
            } else if ($log->consume('blk')) {
                $game->pitching()->evt('BLK');
                // For each runner, advance one base.
                foreach ($game->bases as $k => $p) {
                    if ($p) {
                        $this->logBuffer($game->bases[$k]->person->lastName);
                        $this->advance($game, $k, $k+1);
                        $game->advanceRunner($p, 1, true);
                        $this->log($this->humanBuffer . ".");
                    }
                }
            } else if ($log->consume(',')) {
                // We're into the play section.
                $actions = preg_split('/,/', $log);
                $br = array_shift($actions);
                if (count($actions) > 3) {
                    $this->handleBattedBall($game, array_pop($actions), new StringConsumer($br));
                }
                // if (count($actions) > 3) {
                //     $this->handlePitchedBall(array_pop($actions));
                // }

                // Handle base runners.
                foreach (array_reverse($actions, true) as $b => $action) {
                    if (!$action) continue;
                    $this->logBuffer($game->bases[$b]->person->lastName);
                    foreach (preg_split('/\//', $action) as $event) {
                        $b = $this->handleBaseEvent($game, $b, $event);
                    }
                    $this->log($this->humanBuffer . ".");
                }
                if ($br) {
                    $this->logBuffer($game->hitting()->person->lastName);
                    $b = -1;
                    foreach (preg_split('/\//', $br) as $event) {
                        $event = new StringConsumer($event);
                        if ($b > -1) {
                            $b = $this->handleBaseEvent($game, $b, $event);
                        } elseif ($event->consume('K')) {
                            $game->hitting()->evt('AB');
                            $game->hitting()->evt('SO');
                            $game->pitching()->evt('K');
                            $tb = self::getBases($event);
                            $this->logBuffer("strikes out,");
                            if ($event == 'WP') {
                                $game->pitching()->evt('WP');
                                $b = $this->advance($game, -1, $tb - 1);
                                $game->advanceRunner($game->hitting(), $tb, true);
                                $this->logBuffer(" on wild pitch");
                            } elseif ($event == 'PB') {
                                $game->fielding(2)->evt('PB');
                                $b = $this->advance($game, -1, $tb - 1);
                                $game->advanceRunner($game->hitting(), $tb, false, true);
                                $this->logBuffer(" on passed ball");
                            } elseif ($event == 'BTS') {
                                $game->fielding(2)->evt('PO');
                                $game->out();
                                $this->logBuffer("on bunted third strike");
                            } else {
                                if ($this->handleFielding($game, $event)) {
                                    $b = $this->advance($game, -1, $tb - 1);
                                    $game->advanceRunner($game->hitting(), $tb, false, true);
                                }
                            }
                        } elseif ($event->consume('BB')) {
                            $game->hitting()->evt('BBs');
                            $game->pitching()->evt('BB');
                            $b = $this->advance($game, -1, 0);
                            $game->advanceRunner($game->hitting(), 1);
                            $this->logBuffer("walks");
                        } elseif ($event->consume('HBP')) {
                            $game->hitting()->evt('HPB');
                            $game->pitching()->evt('HBP');
                            $b = $this->advance($game, -1, 0);
                            $game->advanceRunner($game->hitting(), 1);
                            $this->logBuffer("hit by pitch");
                        } elseif ($event->consume('CI')) {
                            $game->hitting()->evt('CI');
                            $game->fielding('2')->evt('E');
                            $b = $this->advance($game, -1, 0);
                            $game->advanceRunner($game->hitting(), 1, false);
                        } elseif (($sac = $event->consume('SAF')) ||
                                  ($sac = $event->consume('SAB'))) {
                            $tb = self::getBases($event);
                            $game->hitting()->evt($sac);
                            $hit = true;
                            if ($this->handleFielding($game, $event, $hit)) {
                                $b = $this->advance($game, -1, $tb - 1);
                                $game->advanceRunner($game->hitting(), $tb, $hit, !$hit);
                            }
                        } elseif (($bb = $event->consume('G')) ||
                                  ($bb = $event->consume('FF')) ||
                                  ($bb = $event->consume('F')) ||
                                  ($bb = $event->consume('L')) ||
                                  ($bb = $event->consume('PF')) ||
                                  ($bb = $event->consume('P'))) {
                            $game->hitting()->evt('AB');
                            $game->hitting()->evt("BIP$bb");
                            $hit = true;
                            $tb = self::getBases($event);
                            if ($this->handleFielding($game, $event, $hit)) {
                                $game->advanceRunner($game->hitting(), $tb, $hit, !$hit);
                                if ($hit) {
                                    $game->hitting()->evt("$tb");
                                    $game->pitching()->evt('HA');
                                    $this->logBuffer(self::HIT[$tb] . ",");
                                }
                                if ($tb < 4) {
                                    $b = $this->advance($game, -1, $tb - 1);
                                } else {
                                    $game->score[$game->half]++;
                                    $game->hitting()->evt('R');
                                    $hit && $game->hitting()->evt('RBI');
                                }
                            }
                            $this->logBuffer("on a " . self::TRAJECTORIES[$bb]);
                        } elseif ($event->consume('MFF')) {
                            // Muffed foul fly, error, At Bat continues.
                            // Need to handle batter being an expected out.
                            $this->handleFielding($game, "E{$event}");
                            $game->advanceRunner($game->hitting(), 0, false, true);
                            return;
                        } else {
                            $game->hitting()->evt('AB');
                            $b = $this->handleBaseEvent($game, $b, $event);
                        }
                    }
                    $this->log($this->humanBuffer . ".");
                    $game->hitting()->evt('PA');
                    $game->pitching()->evt('BFP');
                    $game->batterUp();
                } else {
                    $this->log("With {$game->hitting()->person->lastName} batting, ");
                }
                $this->logA("{$game->outs} out(s).");
                if ($game->outs >= 3) {
                    $nf = new NumberFormatter('en_US', NumberFormatter::ORDINAL);
                    $this->game_event = 'End of the ' . ($game->half ? '' : 'top of the ') . $nf->format($game->inning) . ' inning.';
                    $this->game_event .= " {$game->away_team->short_name} {$game->score[0]} to {$game->home_team->short_name} {$game->score[1]}.";
                    $game->sideAway();
                }
                return;
            }
        }
    }

    public static function getBases(StringConsumer $event): int {
        switch (true) {
            case $event->consume('`'):
                return -4;
            case $event->consume('$'):
                return 4;
            case $event->consume('#'):
                return 3;
            case $event->consume('@'):
                return 2;
            case $event->consume('!'):
            default:
                return 1;
        }
    }

    public function handleBaseEvent(Game $game, int $b, $event) {
        $matches = [];
        $countStats = true;
        $hit = true;
        $runner = $game->bases[$b];
        if (preg_match('/^\((.*)\)$/', $event, $matches)) {
            $event = $matches[1];
            $countStats = false;
        }
        $event = new StringConsumer($event);

        if ($event->consume('ER')) {
            $game->runners[$runner->id]['pitcher']->evt('ER');
        } elseif ($event->consume('UR')) {
            $stats = $game->runners[$runner->id]['pitcher']->stats;
            $stats['ER'] = ($stats['ER'] ?? 0) - 1;
            $game->runners[$runner->id]['pitcher']->stats = $stats;
        }

        $bases = self::getBases($event);
        $stat = null;
        if ($stat = $event->consume('SB')) {
            $countStats && $game->fielding(2)->evt('CSB');
            $game->advanceRunner($runner, 1);
            $this->logBuffer("on stolen base");
        } elseif ($event->consume('CS')) {
            $countStats && $game->fielding(2)->evt('CCS');
            $this->logBuffer("caught stealing");
            if (!$this->handleFielding($game, $event, $hit, $countStats)) {
                $bases = -10000000000;
                $runner->evt('CS');
                $game->advanceRunner($runner, $bases);
            } else {
                $game->advanceRunner($runner, $bases, false, true);
            }
        } elseif ($event->consume('PB')) {
            $countStats && $game->fielding(2)->evt('PB');
            $game->advanceRunner($runner, $bases, false);
            $this->logBuffer('on a passed ball');
        } elseif ($event->consume('WP')) {
            $countStats && $game->pitching()->evt('WP');
            $game->advanceRunner($runner, $bases, true);
            $this->logBuffer('on a wild pitch');
        } elseif ($event->empty() && ($b + $bases > 2)) {
            // NOTE: for GIDP or ROE 2 outs need to use FC.
            $countStats && $game->hitting()->evt('RBI');
            $game->advanceRunner($runner, $bases);
        } elseif ($event->consume('PO')) {
            $countStats && $game->pitching()->evt('POs');
            if ($this->handleFielding($game, $event, $hit, $countStats)) {
                $game->advanceRunner($runner, $bases, false, true);
            }
        } elseif ($event->consume('MB') ||
                    $event->consume('PPR') ||
                    $event->consume('ROL') ||
                    $event->consume('INT') ||
                    $event->consume('RRO') ||
                    $event->consume('HBB') ||
                    $event->consume('UA') ||
                    $bases < 0) {
            $this->handleFielding($game, $event, $hit, $countStats);
            $this->logBuffer('unusually');
            $bases = -10000000000;
            $game->advanceRunner($runner, $bases);
        } elseif ($event->consume('FC')) {
            $this->logBuffer('on a fielder\'s choice');
            $game->advanceRunner($runner, $bases);
        } else {
            $descisive = str_contains($event, 'WT') || str_contains($event, 'E');
            if ($this->handleFielding($game, $event, $hit, $countStats)) {
                $game->advanceRunner($runner, $bases, $hit, $descisive);
            } else {
                $bases = -10000000000;
                $game->advanceRunner($runner, $bases);
            }
        }
        if ($stat) $countStats && $game->bases[$b]->evt($stat);
        $this->advance($game, $b, $b + $bases);
        return $b + $bases;
    }

    /**
     * @return bool true if no out was made.
     */
    public function handleFielding(Game $game, StringConsumer|string $event, bool &$hit = true, bool $countStats = true): bool {
        if (!((string)$event)) return true;
        if ((string)$event === 'FC') {
            $hit = false;
            return true;
        }
        $handled = [];
        $handlers = preg_split('/-/', $event);
        foreach ($handlers as $k => $pos) {
            $pos = new StringConsumer($pos);
            if ($pos->consume('WT') || $pos->consume('E')) {
                // Decisive, remove the runner.
                $countStats && $game->fielding($pos)->evt('E');
                $countStats && $game->fielding($pos)->evt("E.$pos");
                $this->logBuffer('error by ' . self::POSITIONS[(string)$pos]);
                $hit = false;
                return true;
            } elseif ($pos->consume('wt') || $pos->consume('e')) {
                $countStats && $game->fielding($pos)->evt('E');
                $countStats && $game->fielding($pos)->evt("E.$pos");
                $this->logBuffer('error by ' . self::POSITIONS[(string)$pos]);
                $hit = false;
                return true;
            } elseif ($k < count($handlers) - 1 && !array_key_exists((string)$pos, $handled)) {
                $countStats && $game->fielding($pos)->evt('A');
                $countStats && $game->fielding($pos)->evt("A.$pos");
                $this->logBuffer(self::POSITIONS[(string)$pos] . ' to');
                $handled[(string)$pos] = true;
            } elseif ($k == count($handlers) - 1) {
                $this->logBuffer('put out by ' . self::POSITIONS[(string)$pos]);
                $countStats && $game->fielding($pos)->evt('PO');
                $countStats && $game->fielding($pos)->evt("PO.$pos");
                $game->out();
                return false;
            }
        }
        return true;
    }

    public function advance(Game $game, float $from, float $to) {
        throw_unless($to > 2 || $to < 0 || $game->bases[$to] === null);
        throw_unless($from < 0 || $game->bases[$from] !== null);
        if ($to < 0) {}
        elseif ($to < 3) {
            $game->bases[$to] = $game->bases[$from] ?? $game->hitting();
            $this->logBuffer("advances to " . self::BASES[$to]);
        } else {
            $game->bases[$from]->evt('R');
            $game->score[$game->half]++;
            $this->logBuffer("scores");
        }
        if ($from >= 0) $game->bases[$from] = null;
        return $to;
    }

    private function handleBattedBall(Game $game, string $action, StringConsumer $br) {
        if (empty($action)) return;
        $battedBall = new BallInPlay([
            'position' => array_map(fn ($p) => round($p, 2), explode(':', $action)),
            'type' => match(true) {
                $br->consume('G') && true => 'G',
                $br->consume('FF') && true => 'F',
                $br->consume('F') && true => 'F',
                $br->consume('L') && true => 'L',
                $br->consume('PF') && true => 'P',
                $br->consume('P') && true => 'P',
                $br->consume('SAF') && true => 'F',
                $br->consume('SAB') && true => 'G',
                default => null,
            },
            'fielders' => array_map(fn ($p) => $game->fielding($p)?->id, [1, 2, 3, 4, 5, 6, 7, 8, 9]),
        ]);
        $battedBall->player()->associate($game->hitting());

        if ($this->exists) {
            $battedBall->play()->associate($this);
            $battedBall->save();
        } else {
            $this->tempBallInPlay = $battedBall;
        }
    }

    private function log(?string $msg) {
        if (!$msg) return;
        if (!$this->human) {
            $this->human = $msg;
        } else {
            $this->human = "{$msg} {$this->human}";
        }
        $this->humanBuffer = null;
    }

    private function logA(?string $msg) {
        if (!$msg) return;
        if (!$this->human) {
            $this->human = $msg;
        } else {
            $this->human = "{$this->human} {$msg}";
        }
        $this->humanBuffer = null;
    }

    private function logBuffer(string $msg) {
        if (!$this->humanBuffer) {
            $this->humanBuffer = $msg;
        } else {
            $this->humanBuffer = "{$this->humanBuffer} {$msg}";
        }
    }

    public function ballInPlay(): HasOne
    {
        return $this->hasOne(BallInPlay::class);
    }
}

class StringConsumer {
    private string $string;
    private int $index = 0;
    public int $length;

    public function __construct(StringConsumer|string $string) {
        if ($string instanceof StringConsumer) {
            $this->string = $string->string;
            $this->index = $string->index;
            $this->length = $string->length;
            return;
        }
        $this->string = $string;
        $this->length = strlen($string);
    }

    public function __get(string $pos): string {
        return $this->string[$this->index + intval($pos)];
    }

    public function empty(): bool {
        return $this->length <= 0;
    }

    public function consume(int|string $consume) : string|false {
        if (is_string($consume)) {
            if (substr($this->string, $this->index, strlen($consume)) === $consume) {
                $this->index += strlen($consume);
                $this->length -= strlen($consume);
                return $consume;
            }
        } else if (is_int($consume)) {
            $this->index += $consume;
            $this->length -= $consume;
            return substr($this->string, $this->index - $consume, $consume);
        }
        return false;
    }

    public function upto($delim) : string {
        $lim = strpos($this->string, $delim, $this->index);
        if ($lim === false) {
            $result = substr($this->string, $this->index);
            $this->index += $this->length;
            $this->length = 0;
            return $result;
        }
        $result = substr($this->string, $this->index, $lim - $this->index);
        $this->length -= $lim - $this->index;
        $this->index = $lim;
        return $result;
    }

    public function __toString()
    {
        return substr($this->string, $this->index);
    }
}
