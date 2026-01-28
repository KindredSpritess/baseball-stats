<?php

namespace App\Console\Commands;

use App\Casts\GameState;
use App\Helpers\StatsHelper;
use App\Models\Game;
use App\Models\Play;
use App\Models\Team;
use Illuminate\Console\Command;

class ExportScorebookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scorebook:export {game : The ID of the game to export} {--team= : Export for specific team (home/away), or both if not specified}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export an Australian style scorebook HTML for a game';

    const BASES = [
        '!' => 1,
        '@' => 2,
        '#' => 3,
        '$' => 4,
    ];
    const CURVES = [
        0 => '╯',
        1 => '╮',
        2 => '╭',
        3 => '╰',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $gameId = $this->argument('game');
        $teamFilter = $this->option('team');

        $game = Game::with(['home_team', 'away_team', 'players.person', 'plays'])
            ->findOrFail($gameId);

        $this->info("Exporting scorebook for Game #{$game->id}");
        $this->info("{$game->away_team->name} @ {$game->home_team->name}");

        $teams = [];
        if (!$teamFilter || $teamFilter === 'away') {
            $teams[] = ['type' => 'away', 'team' => $game->away_team];
        }
        if (!$teamFilter || $teamFilter === 'home') {
            $teams[] = ['type' => 'home', 'team' => $game->home_team];
        }

        $htmlContents = [];
        foreach ($teams as $teamInfo) {
            $htmlContents[] = $this->generateTeamScorebookHtml($game, $teamInfo['type'], $teamInfo['team']);
        }

        // Ensure directory exists
        $dir = storage_path("app/public/scorebooks");
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        // Combine HTML if both teams
        $combinedHtml = implode('<div style="page-break-before: always;"></div>', $htmlContents);

        // Determine filename
        if (count($teams) === 1) {
            $teamInfo = $teams[0];
            $filename = "scorebook_game{$game->id}_{$teamInfo['type']}_{$teamInfo['team']->short_name}.html";
        } else {
            $filename = "scorebook_game{$game->id}_both.html";
        }

        $htmlPath = storage_path("app/public/scorebooks/{$filename}");
        file_put_contents($htmlPath, $combinedHtml);
        $this->info("Saved HTML: {$htmlPath}");

        $this->info('Scorebook export completed successfully!');

        return 0;
    }

    /**
     * Generate HTML for scorebook for a specific team
     */
    private function generateTeamScorebookHtml(Game $game, string $teamType, Team $team): string
    {
        $this->info("Generating scorebook for {$team->name} ({$teamType})...");

        // Prepare data for the scorebook
        $data = $this->prepareScorebookData($game, $teamType, $team);

        // Generate HTML
        return view('scorebook.australian', $data)->render();
    }

    /**
     * Prepare scorebook data from game
     */
    private function prepareScorebookData(Game $game, string $teamType, Team $team): array
    {
        $isHome = $teamType === 'home';
        $teamIndex = $isHome ? 1 : 0;
        $opponentTeam = $isHome ? $game->away_team : $game->home_team;

        // Force load the game state which populates lineup, defense, etc.
        $game->state;

        // Now access the decoded lineup and defense from the game object
        $lineup = $game->lineup[$teamIndex] ?? [];
        $defense = $game->defense[$teamIndex] ?? [];
        $linescore = $game->linescore ?? [[], []];
        $pitchers = $game->pitchers[$teamIndex] ?? [];

        // Build batting order with players
        $battingOrder = [];
        foreach ($lineup as $spotIndex => $playersInSpot) {
            if (!empty($playersInSpot)) {
                foreach ($playersInSpot as $player) {
                    if ($player) {
                        $battingOrder[$player->id] = [
                            'spot' => $spotIndex + 1,
                            'number' => $player->number ?? '',
                            'name' => "{$player->person->lastName}, {$player->person->firstName}",
                            'player' => $player,
                            'positions' => [],
                        ];
                    }
                }
            }
        }
        foreach ($pitchers as $pitcher) {
            if (!isset($battingOrder[$pitcher->id])) {
                $battingOrder[$pitcher->id] = [
                    'spot' => 'P',
                    'number' => $pitcher->number ?? '',
                    'name' => "{$pitcher->person->lastName}, {$pitcher->person->firstName}",
                    'player' => $pitcher,
                    'positions' => [],
                ];
            }
        }

        // Get plays for this team's at-bats
        $plays = $game->plays;

        // Build inning data
        $innings = [];
        $maxInnings = max(12, count($linescore[$teamIndex] ?? []));
        $runs = 0;
        for ($i = 1; $i <= $maxInnings; $i++) {
            $runs += $linescore[$teamIndex][$i - 1] ?? 0;
            $innings[] = [
                'number' => $i,
                'runs' => $linescore[$teamIndex][$i - 1] ?? 0,
                'runs_total' => isset($linescore[$teamIndex][$i - 1]) ? $runs : null,
                'lob' => isset($linescore[$teamIndex][$i - 1]) ? 0 : null,
                'width' => 1,
                'fielding' => [],
                'pitching' => [],
            ];
        }

        // Extract play-by-play data for each batter in each inning
        $batterInningData = $this->extractBatterInningData($game, $teamIndex, $plays, $innings, $battingOrder);
        $this->splitLongInnings($batterInningData, $innings);

        // Get pitchers of record
        $pitchersOfRecord = $game->pitchersOfRecord ?? [
            'winning' => null,
            'losing' => null,
            'saving' => null,
        ];

        return [
            'game' => $game,
            'team' => $team,
            'opponent' => $opponentTeam,
            'isHome' => $isHome,
            'battingOrder' => $battingOrder,
            'innings' => $innings,
            'batterInningData' => $batterInningData,
            'pitchers' => $pitchers,
            'pitchersOfRecord' => $pitchersOfRecord,
            'venue' => $game->location ?? '',
            'date' => $game->firstPitch ? $game->firstPitch->timezone($game->timeZone)->format('Y-m-d') : '',
            'timeStart' => $game->firstPitch ? $game->firstPitch->timezone($game->timeZone)->format('H:i') : '',
            'timeFinish' => $game->duration ? $game->firstPitch->copy()->addMinutes($game->duration)->timezone($game->timeZone)->format('H:i') : '',
            'totalTime' => $game->duration ? sprintf('%d:%02d', intdiv($game->duration, 60), $game->duration % 60) : '',
        ];
    }

    private static function progTotal(array $cur, array $next, string $stat): string
    {
        if ($cur[$stat]) {
            return ($next[$stat] - $cur[$stat]) . " / " . $next[$stat];
        }
        return "{$next[$stat]}";
    }

    /**
     * Extract play data for each batter in each inning
     * 
     * @var Play[] $plays
     * 
     * @return array Format: [batter_spot][inning][quadrant] = play_info
     */
    private function extractBatterInningData(Game $game, int $teamIndex, $plays, array &$inningsData, array &$battingOrder): array
    {
        // This will contain the actual play data for each batter in each inning
        // Format: [batter_spot][inning][][quadrant] = play_info
        $data = [];

        // Prefill batterInningData with empty entries.
        for ($i = 1; $i <= count($game->lineup[$teamIndex]); $i++) {
            $data[$i] = [];
            for ($j = 1; $j <= count($inningsData); $j++) {
                $data[$i][$j] = [new PlateAppearence()];
            }
        }

        $state = new GameState();
        $state->get($game, 'state', '{}', []);

        $team = $teamIndex === 0 ? $game->away_team : $game->home_team;

        $totals = [
            'b' => 0,
            's' => 0,
            'p' => 0,
            'bfp' => 0,
            'h' => 0,
            'lob' => 0,
        ];

        foreach ($plays as $play) {
            /** @var Play $play */
            // Skip comments and announcements.
            if ($play->play[0] === '!' || $play->play[0] === '#') {
                continue;
            }

            $matches = [];
            // Strip off batted ball, as it's unused.
            preg_match('/^(.*,)(\d+(\.\d+)?:\d+(.\d+)?)$/', $play->getOriginal('play'), $matches);
            $play->play = $matches[1] ?? $play->play;
            $ballInPlay = $matches[2] ?? null;

            $inning = $game->inning;
            $atbat = $game->atBat[$teamIndex] + 1;
            $outs = $game->outs;

            $runners = [];
            try {
                $pitcher = $game->pitching();
            } catch (\Exception $e) {
                $pitcher = null;
            }
            // We need to work out which spots are on base for this play.
            foreach ($game->bases as $baseIndex => $runner) {
                if (!$runner) continue;
                foreach ($game->lineup[$teamIndex] as $spot => $players) {
                    if (end($players)?->id === $runner->id) {
                        $runners[$baseIndex] = $spot + 1;
                        break;
                    }
                }
            }
            $runnerMeta = [];
            foreach ($game->runners as $k => $runner) {
                $runnerMeta[$k] = &$game->runners[$k];
            }

            $play->apply($game);

            if (str_starts_with($play->play, 'Game Over')) {
                if ($play->inning_half === $teamIndex) {
                    $pitcher = $pitchers[count($pitchers) - 1];
                    $nextTotals = [
                        'b' => $pitcher?->stats['Balls'] ?? 0,
                        's' => $pitcher?->stats['Strikes'] ?? 0,
                        'p' => $pitcher?->stats['Pitch'] ?? 0,
                        'bfp' => $pitcher?->stats['BFP'] ?? 0,
                        'h' => $pitcher?->stats['HA'] ?? 0,
                        'lob' => count(array_filter($game->bases)) + $totals['lob'],
                    ];
                    $nextTotals['p'] += $nextTotals['b'] + $nextTotals['s'];
                    if ($nextTotals['p'] !== $totals['p']) {
                        $inningsData[$inning - 1]['pitching'][] = [
                            'pitcher' => $pitcher,
                            'b' => self::progTotal($totals, $nextTotals, 'b'),
                            's' => self::progTotal($totals, $nextTotals, 's'),
                            'p' => self::progTotal($totals, $nextTotals, 'p'),
                            'bfp' => self::progTotal($totals, $nextTotals, 'bfp'),
                            'h' => self::progTotal($totals, $nextTotals, 'h'),
                        ];
                        $inningsData[$inning - 1]['lob'] = self::progTotal($totals, $nextTotals, 'lob');
                    }
                }
                continue;
            }

            if ($play->inning_half === $teamIndex && ($game->inning !== $inning || $game->half !== $teamIndex)) {
                // Inning changed, reset at-bat count for new inning
                // Check that the batter at-bat 
                end($data[$game->atBat[$teamIndex]+1][$inning + 1])->inning_start = true;
                $nextTotals = [
                    'b' => $pitcher?->stats['Balls'] ?? 0,
                    's' => $pitcher?->stats['Strikes'] ?? 0,
                    'p' => $pitcher?->stats['Pitch'] ?? 0,
                    'bfp' => $pitcher?->stats['BFP'] ?? 0,
                    'h' => $pitcher?->stats['HA'] ?? 0,
                    'lob' => $game->lob + $totals['lob'],
                ];
                $nextTotals['p'] += $nextTotals['b'] + $nextTotals['s'];
                $inningsData[$inning - 1]['pitching'][] = [
                    'pitcher' => $pitcher,
                    'b' => self::progTotal($totals, $nextTotals, 'b'),
                    's' => self::progTotal($totals, $nextTotals, 's'),
                    'p' => self::progTotal($totals, $nextTotals, 'p'),
                    'bfp' => self::progTotal($totals, $nextTotals, 'bfp'),
                    'h' => self::progTotal($totals, $nextTotals, 'h'),
                ];
                $inningsData[$inning - 1]['lob'] = self::progTotal($totals, $nextTotals, 'lob');
                $totals = $nextTotals;
            }

            // Handle the case of players being added to the lineup.
            if ($play->command) {
                switch (true) {
                    case str_starts_with($play->play, "@{$team->short_name} "):
                        // Player added to lineup
                        // Extract player position from play text after last ': '
                        $position = trim(substr($play->play, strrpos($play->play, ': ') + 1));
                        if ($position === '1') {
                            $player = end($game->pitchers[$teamIndex]);
                        } else {
                            $spot = end($game->lineup[$teamIndex]);
                            $player = end($spot);
                        }
                        $battingOrder[$player->id]['positions'][] = [$game->inning, $game->outs, $position];
                        break;
                    case str_starts_with($play->play, "DC #"):
                        // Player moved.
                        $matches = [];
                        preg_match('/DC #(\d+) -> (.+)/', $play->play, $matches);
                        if ($game->half === $teamIndex) {
                            if ($matches[2] === '1') {
                                $inningsData[$inning - 1]['fielding'][] = ['PC' => true];
                                if (isset(end($data[$atbat][$inning])->result[0])) {
                                    $data[$atbat][$inning][] = new PlateAppearence();
                                }
                                end($data[$atbat][$inning])->pitcher_change = true;

                                $pitchers = $game->pitchers[($teamIndex + 1) % 2];
                                $pitcher = $pitchers[count($pitchers) - 2];
                                $nextTotals = [
                                    'b' => $pitcher?->stats['Balls'] ?? 0,
                                    's' => $pitcher?->stats['Strikes'] ?? 0,
                                    'p' => $pitcher?->stats['Pitch'] ?? 0,
                                    'bfp' => $pitcher?->stats['BFP'] ?? 0,
                                    'h' => $pitcher?->stats['HA'] ?? 0,
                                ];
                                $nextTotals['p'] += $nextTotals['b'] + $nextTotals['s'];
                                if ($nextTotals['p'] !== $totals['p']) {
                                    $inningsData[$inning - 1]['pitching'][] = [
                                        'pitcher' => $pitcher,
                                        'b' => self::progTotal($totals, $nextTotals, 'b'),
                                        's' => self::progTotal($totals, $nextTotals, 's'),
                                        'p' => self::progTotal($totals, $nextTotals, 'p'),
                                        'bfp' => self::progTotal($totals, $nextTotals, 'bfp'),
                                        'h' => self::progTotal($totals, $nextTotals, 'h'),
                                    ];
                                }
                                $totals = [
                                    'b' => 0,
                                    's' => 0,
                                    'p' => 0,
                                    'bfp' => 0,
                                    'h' => 0,
                                    'lob' => $totals['lob'],
                                ];

                            } else if (!array_intersect_key(['PC' => true, 'DC' => true], end($inningsData[$inning - 1]['fielding']) ?: [])) {
                                $inningsData[$inning - 1]['fielding'][] = ['DC' => true];
                            }
                        } else {
                            $player = end($game->lineup[$teamIndex][intval($matches[1]) - 1]);
                            array_unshift($battingOrder[$player->id]['positions'], [$game->inning, $game->outs, $matches[2]]);
                        }
                        break;
                }
            }

            if ($play->command || $play->inning_half !== $teamIndex) {
                continue;
            }

            if (end($data[$atbat][$inning])?->results[0] ?? null) {
                $data[$atbat][$inning][] = new PlateAppearence();
            }

            $parts = explode(',', $play->play);

            end($data[$atbat][$inning])->pitches .= $parts[0];
            end($data[$atbat][$inning])->pitch_total = (new StatsHelper($game->defense[($teamIndex + 1) % 2][1]->stats))->derive()->Pitches;

            for ($i = 1; $i <= 4; $i++) {
                if ($parts[$i] ?? null) {
                    $b = $i - 1;
                    $plays = explode('/', $parts[$i]);
                    $spot = $i === 1 ? $atbat : ($runners[$i - 2] ?? null);
                    foreach ($plays as $playSegment) {
                        $playResult = $this->extractPlayResult($playSegment, $atbat);
                        [$note, $colour, $bases] = $playResult;
                        if ($colour === 'green' && $ballInPlay) {
                            // Locate ball in play position.
                            $note .= $this->locateBallInPlay($ballInPlay);
                        }
                        if (count($playResult) > 3) {
                            $inningsData[$inning - 1]['fielding'][] = $playResult[3];
                        }
                        if ($bases < 0) {
                            end($data[$spot][$inning])->out_number = ++$outs;
                            end($data[$spot][$inning])->results[$b++] = [$note, $colour];
                        } else {
                            while ($bases--) {
                                if ($bases) {
                                    end($data[$spot][$inning])->results[$b++] = [self::CURVES[$b-1], $colour];
                                } else {
                                    end($data[$spot][$inning])->results[$b++] = [$note, $colour];
                                }
                            }
                            if ($b === 4) {
                                // Run scored.
                                end($data[$spot][$inning])->run_earned = false;
                            }
                        }
                    }
                }
            }
            $this->correctEarnedRuns($game, $teamIndex, $data, $inning, $runnerMeta);
        }

        return $data;
    }

    private function locateBallInPlay(string $ballInPlay): string
    {
        $ballInPlay = trim($ballInPlay, ',');
        $parts = explode(':', $ballInPlay);
        $x = isset($parts[0]) ? floatval($parts[0]) : null;
        $y = isset($parts[1]) ? floatval($parts[1]) : null;

        // TODO: return the correct fielder for the batted ball location.
        return 1;
    }

    private function correctEarnedRuns(Game $game, int $teamIndex, array &$data, int $inning, array $runnerMeta)
    {
        // Go through each batter's data for the inning
        foreach ($data as $spot => &$inningData) {
            if (!isset($inningData[$inning])) {
                continue;
            }
            $batterInning = &$inningData[$inning];
            if (end($batterInning)->run_earned === false) {
                // Check if the run was actually earned
                $atbat = $spot;
                $runnerKey = end($game->lineup[$teamIndex][$atbat - 1])->id ?? null;
                $meta = $runnerMeta[$runnerKey] ?? [];
                if ($meta && $meta['earned'] >= 4 && $meta['expectedOuts'] < 3) {
                    end($batterInning)->run_earned = true;
                }
            }
        }
    }

    /**
     * Extract a simplified play result for scorebook notation
     */
    private function extractPlayResult(string $playText, int $atbat): array
    {
        // Parse play text to extract key result
        // This is a simplified version - full implementation would parse all play notation

        if ($playText === 'K2') {
            return ['K2', 'blue', -1, ['PO' => 2]]; // Strikeout
        } elseif ($playText === 'KBTS') {
            return ['KBTS', 'blue', -1, ['PO' => 2]]; // Strikeout
        } elseif ($playText === 'KPB') {
            return ['<span style="color:blue;">K</span><span style="color:red">PB</span>', 'black', 1];
        } elseif ($playText === 'KWP') {
            return ['KWP', 'blue-text', 1];
        } elseif (str_contains($playText, 'BB')) {
            return ['BB', 'blue', 1]; // Walk
        } elseif (str_contains($playText, 'IBB')) {
            return ['IBB', 'blue', 1]; // Intentional walk
        } elseif (str_contains($playText, 'HBP')) {
            return ['HBP', 'blue', 1]; // Hit by pitch
        } elseif (preg_match('/^SAF(E?)(\d)$/', $playText, $matches)) {
            // Sac fly
            $error = boolval($matches[1]);
            return ['<span style="color:blue;">S</span>' . ($error ? "<span style=\"color:red\">MF{$matches[2]}</span>" : "F{$matches[2]}"), 'black', $matches[1] ? 1 : -1, [($error ? 'E' : 'PO') => $matches[2]]];
        } elseif (preg_match('/^F?[FLP](\d)$/', $playText, $matches)) {
            // Fly out, line out, pop out
            return [$playText, 'black', -1, ['PO' => $matches[1]]];
        } elseif (preg_match('/^SAB((\d-)*(WT|E)?(\d))$/', $playText, $matches)) {
            // Sac bunt
            $error = boolval($matches[3]);
            return ['<span style="color:blue;">S</span>' . ($error ? "$matches[2]<span style=\"color:red\">{$matches[3]}{$matches[4]}</span>" : "{$matches[1]}"), 'black', $error ? 1 : -1, [($error ? 'E' : 'PO') => $matches[4], 'A' => str_replace('-', '', $matches[2])]];
        } elseif (preg_match('/^[BG]?`?(\d)$/', $playText, $matches)) {
            // Unassisted ground out
            return ["UA$matches[1]", 'black', -1, ['PO' => $matches[1]]];
        } elseif (preg_match('/^[FLPGB]?([!@#$]?)(((\d-)*)(E|e|WT|wt)(\d))$/', $playText, $matches)) {
            // Error play
            return [$matches[2], 'red', self::BASES[$matches[1]] ?? 1, ['E' => $matches[6], 'A' => str_replace('-', '', $matches[3])]];
        } elseif (preg_match('/^[FLPGB]?(CS|PO)?`?(((\d-)*)(\d))$/', $playText, $matches)) {
            // Fielding play (e.g., 6-3, 4-3, etc.)
            return ["$matches[1]$matches[2]", 'black', -1, ['PO' => $matches[5], 'A' => str_replace('-', '', $matches[3])]];
        } elseif ($playText === 'SB') {
            return ["SB$atbat", 'black', 1]; // Stolen base
        } elseif (in_array($playText, ['WP', '(WP)'])) {
            return ["WP$atbat", 'blue-text', 1]; // Wild pitch
        } elseif (in_array($playText, ['PB', '(PB)'])) {
            return ["PB$atbat", 'red-text', 1];
        } elseif (in_array($playText, ['!', '@', '#', '$'])) {
            return [$atbat, 'black', self::BASES[$playText] ?? 1]; // Advanced on hitter.
        } elseif (preg_match('/^(F?[FLPGB])([@!#\$]?)$/', $playText, $matches)) {
            // Hit: F=fly, L=line, P=pop, G=ground, B=bunt
            // @=double, !=single, #=triple, $=home run
            $symbol = match($matches[2]) {
                '!' => '-',
                '@' => '=',
                '#' => '≡',
                '$' => '≣',
                default => '-',
            };
            return [$symbol, 'green', self::BASES[$matches[2]] ?? 1];
        } elseif (preg_match('/^[FLGPB]?([!@#$]?)FC$/', $playText, $matches)) {
            // Fielder's choice
            return ['FC', 'black', self::BASES[$matches[1]] ?? 1];
        }

        return [$playText, 'black', 1]; // Unknown
    }

    private function splitLongInnings(array &$batterInningData, array &$innings): void
    {
        foreach ($batterInningData as $inningPlates) {
            foreach ($inningPlates as $inning => $plates) {
                $innings[$inning - 1]['width'] = max($innings[$inning - 1]['width'], count($plates));
            }
        }
        foreach ($innings as $ix => &$inning) {
            $width = $inning['width'];
            while ($width > 1) {
                // Remove empty inning.
                $width--;
                if (end($innings)['runs_total'] === null) {
                    array_pop($innings);
                }
            }
            // Work out the batter who started the inning.
            $startingBatter = null;
            foreach ($batterInningData as $spot => $plates) {
                if (isset($plates[$inning['number']]) && $plates[$inning['number']][0]->inning_start) {
                    $startingBatter = $spot;
                    break;
                }
            }

            // Mark the end of the inning for the starting batter.
            if ($startingBatter) {
                $pax = ($innings[$ix - 1]['width'] ?? 1) - 1;
                // Check if it makes sense to put an inning end marker.
                $batterInningData[$startingBatter][$inning['number'] - 1][$pax] ??= new PlateAppearence();
                $pa = &$batterInningData[$startingBatter][$inning['number'] - 1][$pax];
                if (!($pa->results[0] ?? null)) {
                    $pa->inning_end = true;
                }
            }
        }
    }
}

class PlateAppearence
{
    public $pitches = '';
    public $pitch_total = null;
    public $out_number = null;
    public $run_earned = null;
    public $inning_end = false;
    public $inning_start = false;
    public $pitcher_change = false;
    public $results = []; // Each entry: [note, colour]
}
