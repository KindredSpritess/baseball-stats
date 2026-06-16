<?php

namespace App\Http\Controllers;

use App\Helpers\StatsHelper;
use App\Models\BallInPlay;
use App\Models\Game;
use App\Models\Person;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function create() {
        return view('team.create', [
            'seasons' => Season::all(),
        ]);
    }

    public function store(Request $request) {
        $team = new Team($request->validate([
            'name' => 'required|string|max:100',
            'short_name' => 'required|string|max:50',
            'season_id' => 'required|exists:seasons,id',
            'primary_color' => 'nullable|hex_color',
            'secondary_color' => 'nullable|hex_color',
        ]));
        $team->save();
        if ($request->input('action') === 'another') {
            return redirect()->route('team.create', ['season' => $team->season_id]);
        }
        return redirect()->route('team', ['team' => $team->id]);
    }

    public function show(Request $request, Team $team) {
        $players = collect();
        $player_ids = [];
        foreach ($team->players as $player) {
            $id = $player->person->id;
            $player_ids[] = $player->id;
            if (!$players->has($id)) {
                $players[$id] = new StatsHelper([]);
            }
            $players[$id]->merge($player->stats);
        }
        $totals = new StatsHelper([]);
        foreach ($players as &$player) {
            $totals->merge($player);
            $player->derive();
        }
        $totals->derive();
        $people = Player::where('team_id', $team->id)->select('person_id')->distinct()->get()->pluck('person_id');
        $qualified = $totals->GS && $request->query('qualified') !== 'all';

        $player_ids = implode(',', $player_ids);
        if ($player_ids) {
            $pitcherBalls = BallInPlay::whereRaw("JSON_EXTRACT(fielders, '$[0]') IN ($player_ids)")->get()->groupBy(fn($ball) => $ball->pitcher[0]->person_id);
        } else {
            $pitcherBalls = collect();
        }

        $teamGames = Game::whereEnded(true)
            ->where(function($q) use ($team) {
                $q->where('home', $team->id)
                  ->orWhere('away', $team->id);
            })
            ->count();

        return view('team.show', [
            'team' => $team,
            'stats' => $players,
            'totals' => $totals,
            'people' => Person::whereIn('id', $people)->get(),
            'ballsInPlay' => BallInPlay::whereRelation('player', 'team_id', $team->id)->get()->groupBy('player.person_id'),
            'pitchingBIP' => $pitcherBalls,
            'minPA' => $qualified ? $teamGames * ($totals->PA / $totals->GS - 1) : 0,
            'minIP' => $qualified ? $teamGames / 3 : 0,
            'minFI' => $qualified ? $totals->FI / 9 / 2 : 0,
        ]);
    }

    public function games(Team $team) {
        $games = Game::whereEnded(true)
            ->where(function($q) use ($team) {
                $q->where('home', $team->id)
                  ->orWhere('away', $team->id);
            })
            ->orderBy('firstPitch')
            ->get();

        $stats = [];
        $totals = new StatsHelper([]);
        foreach ($games as $game) {
            $gameStats = new StatsHelper([]);
            $game->players()->whereTeamId($team->id)->each(fn (Player $player) => $gameStats->merge($player->stats));
            $gameStats->derive();
            $stats[$game->id] = $gameStats;
            $totals->merge($gameStats);
        }
        $totals->derive();

        $ballsInPlay = BallInPlay::whereRelation('player', 'team_id', $team->id)
            ->whereHas('player', fn($q) => $q->whereIn('game_id', $games->pluck('id')))
            ->get()
            ->groupBy('player.game_id');

        return view('team.games', [
            'team' => $team,
            'games' => $games,
            'stats' => $stats,
            'totals' => $totals,
            'ballsInPlay' => $ballsInPlay,
        ]);
    }

    public function historical(Request $request, Team $team) {
        // First get the list of people who have played for the team.
        // then find their stats for any game they've played in the last n months (default 12).
        $people = Person::whereHas('players', fn($q) => $q->where('team_id', $team->id))->get();
        $players = Player::whereIn('person_id', $people->pluck('id'))->whereHas('game', fn($q) => $q->where('firstPitch', '>=', now()->subMonths($request->query('months', 12))))->get();

        $stats = $people->mapWithKeys(fn($person) => [$person->id => new StatsHelper([])]);
        $player_ids = [];
        foreach ($players as $player) {
            $id = $player->person->id;
            $player_ids[] = $player->id;
            $stats[$id]->merge($player->stats);
        }
        $totals = new StatsHelper([]);
        foreach ($stats as &$player) {
            $totals->merge($player);
            $player->derive();
        }
        $totals->derive();

        if ($player_ids) {
            $player_ids_list = implode(',', $player_ids);
            $pitcherBalls = BallInPlay::whereRaw("JSON_EXTRACT(fielders, '$[0]') IN ($player_ids_list)")->get()->groupBy(fn($ball) => $ball->pitcher[0]->person_id);
        } else {
            $pitcherBalls = collect();
        }

        return view('team.show', [
            'team' => $team,
            'stats' => $stats,
            'totals' => $totals,
            'people' => $people,
            'ballsInPlay' => BallInPlay::whereIn('player_id', $player_ids)->get()->groupBy('player.person_id'),
            'pitchingBIP' => $pitcherBalls,
            'minPA' => 0,
            'minIP' => 0,
            'minFI' => 0,
            'historical' => $request->query('months', 12),
        ]);
    }

    public function edit(Team $team) {
        return view('team.edit', [
            'team' => $team,
            'seasons' => Season::all(),
        ]);
    }

    public function update(Request $request, Team $team) {
        $team->fill($request->validate([
            'name' => 'required|string|max:100',
            'season_id' => 'required|exists:seasons,id',
            'primary_color' => 'nullable|hex_color',
            'secondary_color' => 'nullable|hex_color',
        ]));
        $team->save();
        return redirect()->route('team', ['team' => $team->id]);
    }

    public function calendar(Team $team) {
        // Get all games for the team
        $games = Game::query()
            ->where(function($q) use ($team) {
                $q->where('home', $team->id)
                  ->orWhere('away', $team->id);
            })
            ->orderBy('firstPitch')
            ->get();

        // Generate ICS content
        $icsContent = $this->generateICS($team, $games);

        // Return as ICS file
        return response($icsContent, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $team->short_name . '-schedule.ics"',
        ]);
    }

    private function generateICS(Team $team, $games) {
        // Generate unique identifier for this calendar
        $prodId = '-//Baseball Stats//Team Calendar//EN';
        $uid = 'team-' . $team->id . '@baseball-stats';

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:{$prodId}\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:PUBLISH\r\n";
        $ics .= "X-WR-CALNAME:" . $this->escapeText($team->name) . " Schedule\r\n";
        $ics .= "X-WR-TIMEZONE:UTC\r\n";

        foreach ($games as $game) {
            $ics .= $this->generateEvent($game, $team);
        }

        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

    private function generateEvent(Game $game, Team $team) {
        // Determine if this team is home or away
        $isHome = $game->home == $team->id;
        $opponent = $isHome ? $game->away_team : $game->home_team;
        $teamType = $isHome ? 'Home' : 'Away';

        // Create summary
        $summary = "{$teamType}: {$team->name} vs {$opponent->name}";

        // Create description with proper line breaks for ICS
        $description = "Game Location: {$game->location}\n";
        $description .= "Home Team: {$game->home_team->name}\n";
        $description .= "Away Team: {$game->away_team->name}";

        // Format timestamps for ICS (YYYYMMDDTHHMMSSZ) in UTC
        $startTime = $game->firstPitch->utc()->format('Ymd\\THis\\Z');

        // Calculate end time (add duration if available, otherwise use 3 hours default)
        $duration = $game->duration ?? 180; // minutes
        $dtEnd = $game->firstPitch->copy()->addMinutes($duration);
        $endTime = $dtEnd->utc()->format('Ymd\\THis\\Z');

        // Create unique event ID
        $eventId = 'game-' . $game->id . '@baseball-stats';

        // Format DTSTAMP (current time in UTC)
        $dtstampFormatted = now()->utc()->format('Ymd\\THis\\Z');

        $event = "BEGIN:VEVENT\r\n";
        $event .= "UID:{$eventId}\r\n";
        $event .= "DTSTAMP:{$dtstampFormatted}\r\n";
        $event .= "DTSTART:{$startTime}\r\n";
        $event .= "DTEND:{$endTime}\r\n";
        $event .= "SUMMARY:" . $this->escapeText($summary) . "\r\n";
        $event .= "DESCRIPTION:" . $this->escapeText($description) . "\r\n";
        $event .= "LOCATION:" . $this->escapeText($game->location) . "\r\n";
        $event .= "STATUS:CONFIRMED\r\n";
        $event .= "END:VEVENT\r\n";

        return $event;
    }

    private function escapeText($text) {
        // Escape special characters in ICS text
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(',', '\,', $text);
        $text = str_replace(';', '\;', $text);
        $text = str_replace("\n", '\\n', $text);
        $text = str_replace("\r", '', $text);
        return $text;
    }
}