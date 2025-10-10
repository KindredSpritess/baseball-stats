<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Number;
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
        1 => 'pitcher',
        2 => 'catcher',
        3 => 'first base',
        4 => 'second base',
        5 => 'third base',
        6 => 'shortstop',
        7 => 'left field',
        8 => 'center field',
        9 => 'right field',
    ];

    const TRAJECTORIES = [
        'G' => 'ground ball',
        'L' => 'line drive',
        'F' => 'fly ball',
        'P' => 'pop up',
        'FF' => 'foul fly',
        'PF' => 'pop foul',
    ];

    const OUT_TRAJECTORIES = [
        'G' => 'grounds out to :fielder',
        'L' => 'lines out to :fielder',
        'F' => 'flies out to :fielder',
        'P' => 'pops out to :fielder',
        'FF' => 'flies out to :fielder in foul territory',
        'PF' => 'pops out to :fielder in foul territory',
    ];

    const BASES = [
        '0' => 'first',
        '1' => 'second',
        '2' => 'third',
        '3' => 'home',
    ];

    const HIT = [
        '1' => 'singles',
        '2' => 'doubles',
        '3' => 'triples',
        '4' => 'homers',
    ];

    /** @var ?string */
    private $humanBuffer = null;

    /** @var ?string */
    private $fieldingBuffer = null;

    /** @var ?BallInPlay tempBallInPlay */
    private $tempBallInPlay = null;

    public function apply(Game $game) {
        $log = new StringConsumer($this->play);
        // Comment
        if ($log->consume('#')) {
            return;
        }

        $this->inning = $game->inning;
        $this->inning_half = $game->half;
        $this->plate_appearance = false;

        if ($log->consume('Side Away')) {
            $nf = new NumberFormatter('en_US', NumberFormatter::ORDINAL);
            $this->game_event = 'End of the ' . ($game->half ? '' : 'top of the ') . $nf->format($game->inning) . ' inning.';
            $this->game_event .= " {$game->away_team->short_name} {$game->score[0]} to {$game->home_team->short_name} {$game->score[1]}.";
            $game->sideAway();
        }

        if ($log->consume('Game Over')) {
            $nf = new NumberFormatter('en_US', NumberFormatter::ORDINAL);
            // Because we might get called at the end of an inning, we need to make sure the game's inning is that of the last play.
            $lastPlay = $game->plays()->orderByDesc('id')->first();
            $game->inning = $lastPlay?->inning ?? $game->inning;
            $game->half = $lastPlay?->inning_half ?? $game->half;
            $this->game_event = 'End of the game. Final score: ';
            $this->game_event .= " {$game->away_team->short_name} {$game->score[0]} to {$game->home_team->short_name} {$game->score[1]}.";
            $game->locked = true;
            return;
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
        if ($log->consume('PH @')) {
            $player = $this->insertPlayer($game, $log);
            $this->log($player->person->lastName . " pinch hits for {$game->hitting()->person->lastName}");
            $game->substitute($game->half, $player, $game->hitting());
            return;
        }

        // Pinch Runner
        if ($log->consume('PR')) {
            $replacing = match ($log[0]) {
                '1' => $game->first,
                '2' => $game->second,
                '3' => $game->third,
            };
            $log->consume(' @');
            $player = $this->insertPlayer($game, $log);
            $game->substitute(!$game->top, $player, $replacing);
            return;
        }

        // Defensive Substitution
        if ($log->consume('DSUB @')) {
            $home = ($game->half+1)%2;
            $position = 'EH';
            $player = $this->insertPlayer($game, $log, $position);
            $replacing = $game->defense[$home][$position];
            $this->log($player->person->lastName . " replaces {$replacing->person->lastName} playing " . (Play::POSITIONS[$position] ?? $position));
            $game->substitute($home, $player, $replacing, $position);
            return;
        }

        // Skip Hitter
        if ($log->consume('-')) {
            $game->batterUp();
            return;
        }

        // Defensive swap
        if ($log->consume('DC #')) {
            $player = end($game->lineup[($game->half+1)%2][$log->upto(' -> ') - 1]);
            throw_unless($log->consume(' -> '), 'Expected " -> "');
            $position = (string)$log;
            $game->defense[($game->half+1)%2][$position] = $player;
            $this->log($player->person->lastName . " moves to " . (Play::POSITIONS[$position] ?? $position));
            if ($position == '1') {
                $player->evt('GP');
                $game->expectedOuts = $game->outs;
                $game->pitchers[($game->half+1)%2][] = $player;
            }
            return;
        }

        // Manfred Runner
        if ($log->consume('MF #')) {
            $player = end($game->lineup[($game->half)%2][$log->upto(' -> ') - 1]);
            throw_unless($log->consume(' -> '), 'Expected " -> "');
            $base = (string)$log;
            $game->bases[$base - 1] = $player;
            $this->log("Extra runner {$player->person->lastName} placed at " . Number::ordinal($base) . ".");
            $this->logA(" {$game->away_team->short_name} {$game->score[0]} to {$game->home_team->short_name} {$game->score[1]}.");
            return;
        }

        // Override Count
        if ($log->consume('SC ')) {
            $count = [];
            throw_unless(preg_match('<^(\d)-(\d)$>', (string)$log, $count), 'Expected count format "X-Y"');
            $game->balls = intval($count[1]);
            $game->strikes = intval($count[2]);
            $this->log("Count set to {$log}.");
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
                foreach (array_reverse($game->bases, true) as $k => $p) {
                    if ($p) {
                        $this->logBuffer($game->bases[$k]->person->lastName);
                        $this->advance($game, $k, $k+1);
                        $game->advanceRunner($p, 1, true);
                        $this->log($this->humanBuffer . ".");
                    }
                }
                $this->log("{$game->pitching()->person->lastName} balks.");
            } else if ($log->consume(',')) {
                // We're into the play section.
                $actions = preg_split('/,/', $log);
                $br = array_shift($actions);
                if (count($actions) > 3) {
                    $ballLocation = array_pop($actions);
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
                    $this->plate_appearance = true;
                    $this->logBuffer($game->hitting()->person->lastName);
                    $b = -1;
                    foreach (preg_split('/\//', $br) as $event) {
                        $event = new StringConsumer($event);
                        if ($b > -1) {
                            $this->logBuffer(",");
                            $b = $this->handleBaseEvent($game, $b, $event);
                        } elseif ($event->consume('K')) {
                            $game->hitting()->evt('AB');
                            $game->hitting()->evt('SO');
                            $game->pitching()->evt('K');
                            $tb = self::getBases($event);
                            $this->logBuffer("strikes out");
                            if ($event == 'WP') {
                                $game->pitching()->evt('WP');
                                $b = $this->advance($game, -1, $tb - 1, "and reaches :base on wild pitch");
                                $game->advanceRunner($game->hitting(), $tb, true);
                            } elseif ($event == 'PB') {
                                $game->fielding(2)->evt('PB');
                                $b = $this->advance($game, -1, $tb - 1, "and reaches :base on passed ball");
                                $game->advanceRunner($game->hitting(), $tb, false, true);
                            } elseif ($event == 'BTS') {
                                $game->fielding(2)->evt('PO');
                                $game->out();
                                $this->logBuffer("on bunted third strike");
                            } elseif ($event == '2') {
                                $game->fielding(2)->evt('PO');
                                $game->out();
                            } else {
                                if ($this->handleFielding($game, $event)) {
                                    $b = $this->advance($game, -1, $tb - 1, "reaches :base on {$this->fieldingBuffer}");
                                    $game->advanceRunner($game->hitting(), $tb, false, true);
                                }
                            }
                        } elseif ($event->consume('BB')) {
                            $game->hitting()->evt('BBs');
                            $game->pitching()->evt('BB');
                            $this->logBuffer("walks");
                            $b = $this->advance($game, -1, 0, false);
                            $game->advanceRunner($game->hitting(), 1);
                        } elseif ($event->consume('HBP')) {
                            $game->hitting()->evt('HPB');
                            $game->pitching()->evt('HBP');
                            $b = $this->advance($game, -1, 0, false);
                            $game->advanceRunner($game->hitting(), 1);
                            $this->logBuffer("hit by pitch");
                        } elseif ($event->consume('CI')) {
                            $game->hitting()->evt('CI');
                            $game->fielding('2')->evt('E');
                            $b = $this->advance($game, -1, 0, "reaches :base on catcher's interference");
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
                            // reaches on an error by the fielder
                            // grounds out to the fielder.
                            $tb = self::getBases($event);
                            if ($this->handleFielding($game, $event, $hit)) {
                                $game->advanceRunner($game->hitting(), $tb, $hit, !$hit);
                                $format = null;
                                if ($hit) {
                                    $game->hitting()->evt("$tb");
                                    $game->pitching()->evt('HA');
                                    $format = __(":type on a :trajectory", [
                                        'type' => self::HIT[$tb],
                                        'trajectory' => self::TRAJECTORIES[$bb],
                                    ]);
                                } else {
                                    $format = __("reaches :base on {$this->fieldingBuffer}", [
                                        'type' => $tb < 4 ? self::HIT[$tb] : 'scores',
                                        'base' => self::BASES[$tb - 1],
                                    ]);
                                }
                                if ($tb < 4) {
                                    $b = $this->advance($game, -1, $tb - 1, $format);
                                } else {
                                    $game->score[$game->half]++;
                                    $this->run_scoring = true;
                                    $game->hitting()->evt('R');
                                    $hit && $game->hitting()->evt('RBI');
                                }
                            } else {
                                $this->logBuffer(__(self::OUT_TRAJECTORIES[$bb], ["fielder" => $this->fieldingBuffer]));
                            }
                            $this->handleBattedBall($game, $bb, $hit, $tb, $ballLocation ?? null);
                        } elseif ($event->consume('MFF')) {
                            // Muffed foul fly, error, At Bat continues.
                            // Need to handle batter being an expected out.
                            $this->handleFielding($game, "E{$event}");
                            $this->plate_appearance = false;
                            $this->log("With {$game->hitting()->person->lastName} batting, {$this->fieldingBuffer} on foul fly.");
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
                $this->logA(trans_choice(":outs out.|:outs out.", $game->outs, ['outs' => $game->outs]));
                if ($game->outs >= 3) {
                    $nf = new NumberFormatter('en_US', NumberFormatter::ORDINAL);
                    $this->game_event = 'End of the ' . ($game->half ? '' : 'top of the ') . $nf->format($game->inning) . ' inning.';
                    $this->game_event .= " {$game->away_team->name} {$game->score[0]} to {$game->home_team->name} {$game->score[1]}.";
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
        $runner = $b > -1 ? $game->bases[$b] : $game->hitting();
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

        $logFormat = null;

        $bases = self::getBases($event);
        $stat = null;
        if ($stat = $event->consume('SB')) {
            $countStats && $game->fielding(2)->evt('CSB');
            $game->advanceRunner($runner, 1);
            $logFormat = "steals :base";
        } elseif ($event->consume('CS')) {
            $countStats && $game->fielding(2)->evt('CCS');
            $logFormat = "caught stealing :base";
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
            $logFormat = '[0,2] to :base on a passed ball|[3,*] scores on a passed ball';
        } elseif ($event->consume('WP')) {
            $countStats && $game->pitching()->evt('WP');
            $game->advanceRunner($runner, $bases, true);
            $logFormat = '[0,2] to :base on a wild pitch|[3,*] scores on a wild pitch';
        } elseif ($event->empty() && ($b + $bases > 2)) {
            // NOTE: for GIDP or ROE 2 outs need to use FC.
            $countStats && $game->hitting()->evt('RBI');
            $game->advanceRunner($runner, $bases);
        } elseif ($event->consume('PO')) {
            $countStats && $game->pitching()->evt('POs');
            if (!$this->handleFielding($game, $event, $hit, $countStats)) {
                $bases = -10000000000;
                $runner->evt('CS');
                $game->advanceRunner($runner, $bases);
                $this->logBuffer(__("picked off at :base by :fielding", [
                    'base' => self::BASES[$b],
                    'fielding' => $this->fieldingBuffer,
                ]));
            } else {
                $game->advanceRunner($runner, $bases, false, true);
                $logFormat = '[0,2] picked off, reaches :base on ' . $this->fieldingBuffer . '|[3,*] picked off, scores on ' . $this->fieldingBuffer;
            }
        } elseif (($rule = $event->consume('MB') ?:
                    $event->consume('PPR') ?:
                    $event->consume('ROL') ?:
                    $event->consume('INT') ?:
                    $event->consume('RRO') ?:
                    $event->consume('HBB') ?:
                    $event->consume('UA')) ||
                    $bases < 0) {
            $this->handleFielding($game, $event, $hit, $countStats);
            $logFormat = match($rule) {
                'MB' => 'missed :base, putout by :fielding',
                'PPR' => 'passed runner, putout by :fielding',
                'ROL' => 'out of the baseline when advancing to :base, putout by :fielding',
                'INT' => 'out on interference when advancing to :base, putout by :fielding',
                'RRO' => 'advances :base on a runner\'s obstruction',
                'HBB' => 'hit by a batted ball, putout by :fielding',
                default => 'putout at :base unusually, :fielding',
            };

            $this->logBuffer(__($logFormat, [
                'base' => self::BASES[$b+1],
                'fielding' => $this->fieldingBuffer,
            ]));
            $bases = -10000000000;
            $game->advanceRunner($runner, $bases);
        } elseif ($event->consume('FC')) {
            $logFormat = '[0,2] to :base on throw|[3,*] scores on throw';
            $game->advanceRunner($runner, $bases);
        } else {
            $descisive = str_contains($event, 'WT') || str_contains($event, 'E');
            if ($this->handleFielding($game, $event, $hit, $countStats)) {
                $game->advanceRunner($runner, $bases, $hit, $descisive);
                if ($this->fieldingBuffer !== null) {
                    $logFormat = '[0,2] to :base on ' . $this->fieldingBuffer . '|[3,*] scores on ' . $this->fieldingBuffer;
                }
            } else {
                $bases = -10000000000;
                $game->advanceRunner($runner, $bases);
                $this->logBuffer(__("put out at :base by :fielding", [
                    'base' => self::BASES[$b+1],
                    'fielding' => $this->fieldingBuffer,
                ]));
            }
        }
        if ($stat) $countStats && $game->bases[$b]->evt($stat);
        $this->advance($game, $b, $b + $bases, $logFormat);
        return $b + $bases;
    }

    /**
     * @return bool true if no out was made.
     */
    public function handleFielding(Game $game, StringConsumer|string $event, bool &$hit = true, bool $countStats = true): bool {
        $this->fieldingBuffer = null;
        if (!((string)$event)) return true;
        if ((string)$event === 'FC') {
            $this->fieldingBuffer = 'fielder\'s choice';
            $hit = false;
            return true;
        }
        $handled = [];
        $handlers = preg_split('/-/', $event);
        foreach ($handlers as $k => $pos) {
            $pos = new StringConsumer($pos);
            if ($type = ($pos->consume('WT') || $pos->consume('E'))) {
                // Decisive, remove the runner.
                $countStats && $game->fielding($pos)->evt('E');
                $countStats && $game->fielding($pos)->evt("E.$pos");
                $this->fieldingBuffer = ($type == 'E' ? 'fielding ' : 'throwing ') . 'error by ' . self::POSITIONS[(string)$pos] . ' ' . $game->fielding($pos)->person->lastName;
                $hit = false;
                return true;
            } elseif ($type = ($pos->consume('wt') || $pos->consume('e'))) {
                $countStats && $game->fielding($pos)->evt('E');
                $countStats && $game->fielding($pos)->evt("E.$pos");
                $this->fieldingBuffer = ($type == 'e' ? 'fielding ' : 'throwing ') . 'error by ' . self::POSITIONS[(string)$pos] . ' ' . $game->fielding($pos)->person->lastName;
                $hit = false;
                return true;
            } elseif ($k < count($handlers) - 1 && !array_key_exists((string)$pos, $handled)) {
                $countStats && $game->fielding($pos)->evt('A');
                $countStats && $game->fielding($pos)->evt("A.$pos");
                $this->fieldingBuffer .= self::POSITIONS[(string)$pos] . ' ' . $game->fielding($pos)->person->lastName . ' to ';
                $handled[(string)$pos] = true;
            } elseif ($k == count($handlers) - 1) {
                $countStats && $game->fielding($pos)->evt('PO');
                $countStats && $game->fielding($pos)->evt("PO.$pos");
                $game->out();
                $hit = false;
                $this->fieldingBuffer .= self::POSITIONS[(string)$pos] . ' ' . $game->fielding($pos)->person->lastName;
                return false;
            }
        }
        return true;
    }

    private function insertPlayer(Game $game, StringConsumer $log, string &$position = ''): Player {
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
        if (isset($matches[4])) {
            $player->number = $matches[4];
        }
        $player->push();
        if (isset($matches[5])) {
            $position = $matches[5];
        }

        return $player;
    }

    public function advance(Game $game, int $from, int $to, ?string $logFormat = null) {
        throw_unless($to > 2 || $to < 0 || $game->bases[$to] === null, "Cannot advance to occupied base");
        throw_unless($from < 0 || $game->bases[$from] !== null, "No runner on base to advance");
        if (is_null($logFormat)) {
            $logFormat = "[0,2] to :base|[3,*] scores";
        }
        if ($to < 0) {}
        elseif ($to < 3) {
            $game->bases[$to] = $game->bases[$from] ?? $game->hitting();
            if ($logFormat) $this->logBuffer(trans_choice($logFormat, $to, ["base" => self::BASES[$to]]));
        } else {
            $game->bases[$from]->evt('R');
            $game->score[$game->half]++;
            $this->run_scoring = true;
            if ($logFormat) $this->logBuffer(trans_choice($logFormat, $to, ["base" => 'home']));
        }
        if ($from >= 0) $game->bases[$from] = null;
        return $to;
    }

    private function handleBattedBall(Game $game, string $type, bool $hit, int $bases, ?string $action) {
        if (empty($action)) return;
        $battedBall = new BallInPlay([
            'position' => array_map(fn ($p) => round($p, 2), explode(':', $action)),
            'type' => match($type) {
                'G' => 'G',
                'FF' => 'F',
                'F' => 'F',
                'L' => 'L',
                'PF' => 'P',
                'P' => 'P',
                'SAF' => 'F',
                'SAB' => 'G',
                default => null,
            },
            'result' => $hit ? ($bases < 4 ? "{$bases}B" : 'HR') : 'O',
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
        } else if ($msg == ',') {
            $this->humanBuffer = "{$this->humanBuffer},";
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
