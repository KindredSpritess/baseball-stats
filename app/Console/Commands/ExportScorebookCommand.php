<?php

namespace App\Console\Commands;

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

        // Generate PDF
        $pdf = Pdf::loadView('scorebook.australian', $data);
        $pdf->setPaper('a3', 'landscape');

        // Save to storage
        $filename = "scorebook_game{$game->id}_{$teamType}_{$team->short_name}.pdf";
        $path = storage_path("app/public/scorebooks/{$filename}");
        
        // Ensure directory exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

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

        // Load game state to get lineup information
        $gameState = $game->state ?? [];
        $lineup = $gameState['lineup'][$teamIndex] ?? [];
        $defense = $gameState['defense'][$teamIndex] ?? [];
        $linescore = $gameState['linescore'] ?? [[], []];

        // Get players for this team
        $teamPlayers = $game->players()
            ->where('team_id', $team->id)
            ->with('person')
            ->get();

        // Build batting order with players
        $battingOrder = [];
        foreach ($lineup as $spotIndex => $playersInSpot) {
            if (!empty($playersInSpot)) {
                foreach ($playersInSpot as $playerData) {
                    // Find the actual player
                    $player = $teamPlayers->firstWhere('id', $playerData['id'] ?? null);
                    if ($player) {
                        $battingOrder[] = [
                            'spot' => $spotIndex + 1,
                            'number' => $player->number ?? '',
                            'name' => $player->person->fullName(),
                            'position' => $this->getPlayerPosition($playerData['id'], $defense),
                            'player_id' => $player->id,
                        ];
                        break; // Only take the first player in each spot for now
                    }
                }
            }
        }

        // Get plays for this team's at-bats
        $plays = $game->plays()
            ->where('plate_appearance', true)
            ->whereNotNull('inning')
            ->get()
            ->groupBy('inning');

        // Build inning data
        $innings = [];
        $maxInnings = max(12, count($linescore[$teamIndex] ?? []));
        for ($i = 1; $i <= $maxInnings; $i++) {
            $innings[] = [
                'number' => $i,
                'runs' => $linescore[$teamIndex][$i - 1] ?? 0,
            ];
        }

        // Extract play-by-play data for each batter in each inning
        $batterInningData = $this->extractBatterInningData($game, $teamIndex, $plays, $teamPlayers);

        return [
            'game' => $game,
            'team' => $team,
            'opponent' => $opponentTeam,
            'isHome' => $isHome,
            'battingOrder' => $battingOrder,
            'innings' => $innings,
            'batterInningData' => $batterInningData,
            'venue' => $game->location ?? '',
            'date' => $game->firstPitch ? $game->firstPitch->format('Y-m-d') : '',
            'timeStart' => $game->firstPitch ? $game->firstPitch->format('H:i') : '',
            'timeFinish' => $game->duration ? $game->firstPitch->addMinutes($game->duration)->format('H:i') : '',
            'totalTime' => $game->duration ? sprintf('%d:%02d', intdiv($game->duration, 60), $game->duration % 60) : '',
        ];
    }

    /**
     * Get player's defensive position
     */
    private function getPlayerPosition($playerId, array $defense): string
    {
        foreach ($defense as $position => $playerData) {
            if (isset($playerData['id']) && $playerData['id'] === $playerId) {
                return $position;
            }
        }
        return '';
    }

    /**
     * Extract play data for each batter in each inning
     */
    private function extractBatterInningData(Game $game, int $teamIndex, $playsByInning, $teamPlayers): array
    {
        // This will contain the actual play data for each batter in each inning
        // Format: [batter_index][inning] = play_info
        $data = [];

        // For now, we'll return an empty structure
        // In a full implementation, this would parse the play-by-play data
        // and map it to specific batter-inning combinations
        
        // TODO: Parse plays to extract:
        // - Result of at-bat (hit, out, walk, etc.)
        // - Base running notation
        // - Fielding notation (who made the out)
        // - Scoring notation
        // - Count information
        
        return $data;
    }
}
