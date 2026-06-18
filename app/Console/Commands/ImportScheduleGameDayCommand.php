<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\Season;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Str;

class ImportScheduleGameDayCommand extends Command
{
  protected $signature = 'schedule:import-gameday {season_id? : The ID of the season to import}';
  protected $description = 'Import games, teams and results by scraping GameDay pages. If season_id is provided, only imports games for that season.';

  private PendingRequest $gamedayClient;

  const FINAL_STATUS = ['Results Entered', 'Washed Out'];

  public function handle()
  {
    dump(posix_isatty(STDIN));
    $seasonId = $this->argument('season_id');
    $this->gamedayClient = Http::baseUrl(config('services.gameday.base_url'))
      ->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
      ]);
    if ($seasonId) {
      $this->importSeasonFromGameDay(Season::findOrFail($seasonId));
    } else {
      $seasons = Season::where('competition_details->gameday_comp_id', '!=', null)->get();
      foreach ($seasons as $season) {
        $this->importSeasonFromGameDay($season);
      }
    }
  }

  private function importSeasonFromGameDay(Season $season)
  {
    $deets = $season->competition_details;
    $deets = $deets ?? [];
    $deets['teams'] = $deets['teams'] ?? [];
    $deets['games'] = $deets['games'] ?? [];
    $compId = $deets['gameday_comp_id'] ?? null;

    if (!$compId) {
      // Ask for it.
      $compId = posix_isatty(STDIN) && $this->ask("Season '{$season->name}' does not have a GameDay Comp ID. Please enter the Comp ID to import games for this season, or leave blank to skip:");
      if (!$compId) {
        $this->info("Skipping season '{$season->name}'");
        return;
      }
      $deets['gameday_comp_id'] = $compId;
    }

    $this->info("Importing games for season: {$season->name} from GameDay Comp ID: {$compId}");

    $html = $this->gamedayClient->get('', [
      'a' => 'ROUND',
      'round' => -1,
      'client' => $compId,
    ])
      ->throw()->body();

    // Parse the HTML to extract any other pools, add them to an array of pools.
    $pools = [];
    preg_match_all('/pool=(\d+)&amp;/', $html, $matches);
    if (!empty($matches[1])) {
      $pools = array_unique($matches[1]);
    }

    $unknownGames = collect($deets['games'])->keys();

    // Now we have all the pools, we can import the games for each pool.
    foreach ($pools as $poolId) {
      $this->info("Importing games for pool ID: {$poolId}");
      $html = $this->gamedayClient->get('', [
        'a' => 'ROUND',
        'round' => -1,
        'client' => $compId,
        'pool' => $poolId,
      ])
        ->throw()->body();

      // There's a line that contains 'var matches = ...' followed by a JSON array of games. We can extract that and decode it.
      $matches = [];
      preg_match('/var matches = (\[.*\]);$/m', $html, $matches);
      if (!empty($matches[1])) {
        $games = collect(json_decode($matches[1], true))->filter(fn($game) => !$game['isBye']);
        $unknownGames = $unknownGames->diff($games->pluck('FixtureID'));
        $teams = $games
          ->map(fn($game) => [$game['HomeID'] => $game['HomeName'], $game['AwayID'] => $game['AwayName']])
          ->collapseWithKeys();
        foreach ($teams as $teamId => $teamName) {
          if (!isset($deets['teams'][$teamId])) {
            if (!posix_isatty(STDIN)) {
              $this->warn("Team '{$teamName}' with ID '{$teamId}' does not exist in the database and cannot be imported because this command is not running in an interactive terminal. Skipping this team and any games involving it.");
              return;
            }

            // Try to find a team with this name, and if it doesn't exist, create it.
            $team = $season->teams()->firstWhere(['name' => $teamName]);
            if (!$team) {
              // Ask if we should create the team or use an existing unmapped.
              $existing = $season->teams()->pluck('name')->sort()->toArray();
              $existingOptions = ['Create new team', ...$existing];
              $choice = $this->choice("Team '{$teamName}' does not exist. What would you like to do?", $existingOptions, 0);
              if ($choice === 'Create new team') {
                $team = $season->teams()->create(['name' => $teamName, 'short_name' => Str::initials($teamName, capitalize: true)]);
              } else {
                $team = $season->teams()->firstWhere(['name' => $choice]);
              }
            }
            $deets['teams'][$teamId] = $team->id;
          }
        }

        // Now we have the games for this pool, we can import them into the database.
        // But we need to make sure there aren't already games that exist.
        // We map FixtureID => GameID and store the mapping in the competition details for this season so we can update existing games instead of creating duplicates.
        $games->each(function ($gameMeta) use (&$deets) {
          // First check if the game already exists by looking for the FixtureID in the competition details.
          if (isset($deets['games'][$gameMeta['FixtureID']])) {
            $game = Game::find($deets['games'][$gameMeta['FixtureID']]);
          } else {
            // Check for a game with the same teams on the same date.
            $homeTeamId = $deets['teams'][$gameMeta['HomeID']] ?? null;
            $awayTeamId = $deets['teams'][$gameMeta['AwayID']] ?? null;
            $gameDate = Carbon::parse($gameMeta['TimeDateRaw'], 'Australia/Brisbane')->setTimezone('UTC');
            $game = Game::whereHome($homeTeamId)
              ->whereAway($awayTeamId)
              ->whereDate('firstPitch', $gameDate->toDateString())
              ->first();
          }
          if ($game) {
            $deets['games'][$gameMeta['FixtureID']] = $game->id;
            // Decode state.
            $game->state;
            $game->ended |= \in_array($gameMeta['MatchStatus'], self::FINAL_STATUS);
            $game->status = match ($gameMeta['MatchStatus'] ?? '') {
              'Scheduled' => 'scheduled',
              'In Progress' => 'in_progress',
              'Results Entered' => 'final',
              'Washed Out' => 'washed_out',
              default => $game->status, // Don't overwrite existing status if we don't recognize the new one.
            };
            $game->score = [
              \intval($gameMeta['AwayScore'] ?? 0),
              \intval($gameMeta['HomeScore'] ?? 0),
            ];
            $game->state = 'encode';
            $game->save();
          } else {
            $this->warn("Could not find existing game for: {$gameMeta['HomeName']} vs {$gameMeta['AwayName']} on {$gameMeta['TimeDateRaw']}. Creating new game with FixtureID: {$gameMeta['FixtureID']}");
            $game = new Game([
              'home' => $deets['teams'][$gameMeta['HomeID']] ?? null,
              'away' => $deets['teams'][$gameMeta['AwayID']] ?? null,
              'firstPitch' => Carbon::parse($gameMeta['TimeDateRaw'], 'Australia/Brisbane')->setTimezone('UTC'),
              'location' => $gameMeta['VenueName'] ?? null,
              'duration' => $deets['duration'] ?? null,
              'ended' => \in_array($gameMeta['MatchStatus'], self::FINAL_STATUS),
              'status' => match ($gameMeta['MatchStatus'] ?? '') {
                'Scheduled' => 'scheduled',
                'In Progress' => 'in_progress',
                'Results Entered' => 'final',
                'Washed Out' => 'washed_out',
                default => null,
              },
            ]);
            $game->score = [
              \intval($gameMeta['AwayScore'] ?? 0),
              \intval($gameMeta['HomeScore'] ?? 0),
            ];
            $game->state = 'encode';
            $game->save();
            $deets['games'][$gameMeta['FixtureID']] = $game->id;
          }
        });
      }
    }

    if ($unknownGames->isNotEmpty()) {
      $this->warn("The following FixtureIDs were found in the database but not in the GameDay import. This may mean they were deleted from GameDay, or that they were manually added to the database without a corresponding GameDay FixtureID. You can choose to delete these games from the database to keep it in sync with GameDay, or keep them if you think they might be valid games that just don't have a FixtureID.");
    }
    $unknownGames->each(function ($fixtureId) use (&$deets) {
      $game = Game::find($deets['games'][$fixtureId]);
      if ($game && posix_isatty(STDIN) && $this->confirm("Do you want to delete the game '{$game->homeTeam->name} vs {$game->awayTeam->name}' on {$game->firstPitch->toDateString()}?")) {
        $game->delete();
        unset($deets['games'][$fixtureId]);
      }
    });

    $season->competition_details = $deets;
    $season->save();
  }
}
