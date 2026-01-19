<?php

namespace App\Console\Commands;

use App\Casts\GameState;
use App\Helpers\StatsHelper;
use App\Models\Game;
use App\Models\Team;
use Illuminate\Console\Command;
use Barryvdh\DomPDF\Facade\Pdf;

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
    protected $description = 'Export an Australian style scorebook PDF for a game';

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

        foreach ($teams as $teamInfo) {
            $this->exportTeamScorebook($game, $teamInfo['type'], $teamInfo['team']);
        }

        $this->info('Scorebook export completed successfully!');

        return 0;
    }

    /**
     * Export scorebook for a specific team
     */
    private function exportTeamScorebook(Game $game, string $teamType, Team $team)
    {
        $this->info("Generating scorebook for {$team->name} ({$teamType})...");

        // Prepare data for the scorebook
        $data = $this->prepareScorebookData($game, $teamType, $team);

        // Ensure directory exists
        $dir = storage_path("app/public/scorebooks");
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        // Generate HTML
        $html = view('scorebook.australian', $data)->render();
        $filename = "scorebook_game{$game->id}_{$teamType}_{$team->short_name}.html";
        $htmlPath = storage_path("app/public/scorebooks/{$filename}");
        file_put_contents($htmlPath, $html);
        $this->info("Saved HTML: {$htmlPath}");

        // Generate PDF
        $pdf = Pdf::loadView('scorebook.australian', $data);
        $pdf->setPaper('a3', 'landscape');

        // Save to storage
        $filename = "scorebook_game{$game->id}_{$teamType}_{$team->short_name}.pdf";
        $path = storage_path("app/public/scorebooks/{$filename}");

        $pdf->save($path);

        $this->info("Saved: {$path}");
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
                        $battingOrder[] = [
                            'spot' => $spotIndex + 1,
                            'number' => $player->number ?? '',
                            'name' => "{$player->person->lastName}, {$player->person->firstName}",
                            'position' => $this->getPlayerPosition($player, $defense),
                            'player' => $player,
                        ];
                    }
                }
            }
        }

        // Get plays for this team's at-bats
        $plays = $game->plays;

        // Build inning data
        $innings = [];
        $maxInnings = count($linescore[$teamIndex] ?? []);
        $runs = 0;
        for ($i = 1; $i <= $maxInnings; $i++) {
            $runs += $linescore[$teamIndex][$i - 1] ?? 0;
            $innings[] = [
                'number' => $i,
                'runs' => $linescore[$teamIndex][$i - 1] ?? 0,
                'runs_total' => $runs,
                'lob' => 0, // TODO: Calculate LOB per inning from plays
                'width' => 1, // This will increase if the team bats around.
            ];
        }

        // Extract play-by-play data for each batter in each inning
        $batterInningData = $this->extractBatterInningData($game, $teamIndex, $plays, $battingOrder);

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
            'date' => $game->firstPitch ? $game->firstPitch->format('Y-m-d') : '',
            'timeStart' => $game->firstPitch ? $game->firstPitch->format('H:i') : '',
            'timeFinish' => $game->duration ? $game->firstPitch->copy()->addMinutes($game->duration)->format('H:i') : '',
            'totalTime' => $game->duration ? sprintf('%d:%02d', intdiv($game->duration, 60), $game->duration % 60) : '',
        ];
    }

    /**
     * Get player's defensive position
     */
    private function getPlayerPosition($player, array $defense): string
    {
        foreach ($defense as $position => $defensePlayer) {
            if ($defensePlayer && $defensePlayer->id === $player->id) {
                return $position;
            }
        }
        return '';
    }

    /**
     * Extract play data for each batter in each inning
     * 
     * @var Play[] $plays
     * 
     * @return array Format: [batter_spot][inning][quadrant] = play_info
     */
    private function extractBatterInningData(Game $game, int $teamIndex, $plays, array $battingOrder): array
    {
        // This will contain the actual play data for each batter in each inning
        // Format: [batter_spot][inning][quadrant] = play_info
        $data = [];

        $state = new GameState();
        $state->get($game, 'state', '{}', []);
        foreach ($plays as $play) {
            // Skip comments and announcements.
            if ($play->play[0] === '!' || $play->play[0] === '#') {
                continue;
            }

            // Strip off batted ball, as it's unused.
            $playText = preg_replace('/,\d+(\.\d+)?:\d+(.\d+)?$/', '', $play->play);
            $play->play = $playText;

            $inning = $game->inning;
            $atbat = $game->atBat[$teamIndex] + 1;

            $runners = [];
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

            $play->apply($game);

            if ($play->command || $play->inning_half !== $teamIndex) {
                continue;
            }

            $data[$atbat] ??= [];
            $data[$atbat][$inning] ??= ['pitches' => ''];

            $parts = explode(',', $play->play);

            $data[$atbat][$inning]['pitches'] .= $parts[0];
            $data[$atbat][$inning]['pitch-total'] = (new StatsHelper($game->defense[($teamIndex + 1) % 2][1]->stats))->derive()->Pitches;

            if ($parts[1] ?? null) {
                $data[$atbat][$inning][0] = $this->extractPlayResult($parts[1], $atbat);
            }
            if ($parts[2] ?? null) {
                $data[$runners[0]][$inning][1] = $this->extractPlayResult($parts[2], $atbat);
            }
            if ($parts[3] ?? null) {
                $data[$runners[1]][$inning][2] = $this->extractPlayResult($parts[3], $atbat);
            }
            if ($parts[4] ?? null) {
                $data[$runners[2]][$inning][3] = $this->extractPlayResult($parts[4], $atbat);
            }
        }

        return $data;
    }

    /**
     * Extract a simplified play result for scorebook notation
     */
    private function extractPlayResult(string $playText, int $atbat): array
    {
        // Parse play text to extract key result
        // This is a simplified version - full implementation would parse all play notation

        if ($playText === 'K2') {
            return ['K2', 'blue']; // Strikeout
        } elseif (str_contains($playText, 'BB')) {
            return ['BB', 'blue']; // Walk
        } elseif (str_contains($playText, 'IBB')) {
            return ['IBB', 'blue']; // Intentional walk
        } elseif (str_contains($playText, 'HBP')) {
            return ['HBP', 'blue']; // Hit by pitch
        } elseif (preg_match('/^F?[FLP]\d$/', $playText, $matches)) {
            // Fly out, line out, pop out
            return [$playText, 'black'];
        } elseif (preg_match('/^[BG]\d$/', $playText, $matches)) {
            // Unassisted ground out
            return ["UA$playText", 'black'];
        } elseif (preg_match('/([FLPGBfgplb])([@!#\$])/', $playText, $matches)) {
            // Hit: F=fly, L=line, P=pop, G=ground, B=bunt
            // @=double, !=single, #=triple, $=home run
            $hitType = $matches[1];
            $bases = match($matches[2]) {
                '!' => '1B',
                '@' => '2B',
                '#' => '3B',
                '$' => 'HR',
                default => 'H',
            };
            return [$bases, 'green'];
        } elseif (preg_match('/([0-9\-]+)/', $playText, $matches)) {
            // Fielding play (e.g., 6-3, 4-3, etc.)
            return [$matches[1], 'black'];
        } elseif ($playText === 'SB') {
            return ["SB$atbat", 'black']; // Stolen base
        } elseif (in_array($playText, ['WP', '(WP)'])) {
            return ["WP$atbat", 'blue'];
        } elseif (in_array($playText, ['PB', '(PB)'])) {
            return ["PB$atbat", 'red'];
        } elseif (in_array($playText, ['!', '@', '#', '$'])) {
            return [$atbat, 'black']; // Advanced on hitter.
        }
        
        return [$playText, 'black']; // Unknown
    }
}
