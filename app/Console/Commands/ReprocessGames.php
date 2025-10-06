<?php

namespace App\Console\Commands;

use App\Casts\GameState;
use App\Models\Game;
use App\Models\Play;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReprocessGames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'games:reprocess {game? : The ID of a specific game to reprocess} {--all : Reprocess all games}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprocess existing games by re-applying their play data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $gameId = $this->argument('game');
        $all = $this->option('all');

        if ($gameId) {
            $games = Game::where('id', $gameId)->get();
        } elseif ($all) {
            $games = Game::all();
        } else {
            $this->error('Please specify a game ID or use --all to reprocess all games');
            return 1;
        }

        $this->info("Found {$games->count()} games to reprocess");

        $progressBar = $this->output->createProgressBar($games->count());
        $progressBar->start();

        foreach ($games as $game) {
            try {
                $this->reprocessGame($game);
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->error("Failed to reprocess game {$game->id}: {$e->getMessage()}");
                Log::error("Failed to reprocess game {$game->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('Reprocessing complete!');

        return 0;
    }

    private function reprocessGame(Game $game)
    {
        if ($game->locked) {
            throw new \Exception('Cannot reprocess locked game.');
        }

        // Get the existing plays as raw text
        $existingPlays = $game->plays()->orderBy('id')->pluck('play')->toArray();
        $playsText = implode("\n", $existingPlays);

        if (empty($playsText)) {
            // Skip games with no plays
            return;
        }

        // Delete existing players and plays
        $game->players()->delete();
        $game->plays()->delete();

        // Re-parse and apply plays
        $plays = collect(preg_split("/\n/", $playsText))
            ->filter(fn (string $play) => trim($play))
            ->map(fn (string $play) => new Play(['play' => $play]));

        $gs = new GameState;
        $gs->get($game, 'state', '{}', []);

        foreach ($plays as $k => $play) {
            try {
                $play->apply($game);
            } catch (\Exception $e) {
                Log::error("Error with line $k in game {$game->id}: {$play->play}");
                throw $e;
            }
        }

        $game->plays()->saveMany($plays);
        $game->push();

        // Save lineup players
        foreach ($game->lineup as $lineup) {
            foreach ($lineup as $position) {
                foreach ($position as $player) {
                    $player->save();
                }
            }
        }

        // Force re-encoding of game state
        $gs = new GameState;
        $gs->set($game, '', '', []);
        $game->save();
    }
}