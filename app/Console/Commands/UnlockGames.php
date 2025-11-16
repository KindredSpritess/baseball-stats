<?php

namespace App\Console\Commands;

use App\Models\Game;
use Illuminate\Console\Command;

class UnlockGames extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'games:unlock {game_id* : The ID of the game to unlock}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Unlock a locked game';

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    $gameIds = $this->argument('game_id');

    $games = Game::whereIn('id', $gameIds)->get();
    foreach ($games as $game) {
      $name = "{$game->away_team->name} at {$game->home_team->name} on {$game->firstPitch}";
      if (!$game->locked) {
        $this->info("{$name} is not locked.");
        continue;
      }

      $game->locked = false;
      $game->save();

      $this->info("{$name} has been unlocked.");
    }

    return 0;
  }
}
