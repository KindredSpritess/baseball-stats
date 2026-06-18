<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
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
    $seasonId = $this->argument('season_id');
    $this->gamedayClient = Http::baseUrl(config('services.gameday.base_url'))
      ->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
      ]);
    if ($seasonId) {
      $this->importSeasonFromGameDay(Season::findOrFail($seasonId));
    } else {
      $seasons = Season::whereJsonContains('competition_details', 'gameday_comp_id')->get();
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
      $compId = $this->ask("Season '{$season->name}' does not have a GameDay Comp ID. Please enter the Comp ID to import games for this season, or leave blank to skip:");
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
        $games = json_decode($matches[1], true);
        $teams = collect($games)
          ->filter(fn($game) => !$game['isBye'])
          ->map(fn($game) => [$game['HomeID'] => $game['HomeName'], $game['AwayID'] => $game['AwayName']])
          ->collapseWithKeys();
        foreach ($teams as $teamId => $teamName) {
          if (!isset($deets['teams'][$teamId])) {
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
        collect($games)->filter(fn($game) => !$game['isBye'])->each(function ($gameMeta) use (&$deets) {
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


        // foreach ($games as $game) {
        //   $fixtureId = $game['FixtureID'];
        //   $existingGameId = $fixtureIdToGameId[$fixtureId] ?? null;
        //   $homeTeamId = $game['HomeID'];
        //   $awayTeamId = $game['AwayID'];
        //   $homeTeam = $teamIdToTeam[$homeTeamId] ?? null;
        //   $awayTeam = $teamIdToTeam[$awayTeamId] ?? null;

        //   // If some of the mappings don't exist, we'll first try to find the teams by name, and if they don't exist we'll create them.
      }
    }

    $season->competition_details = $deets;
    $season->save();
  }
}
