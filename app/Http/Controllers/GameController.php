<?php

namespace App\Http\Controllers;

use App\Casts\GameState;
use App\Events\GameUpdated;
use App\Helpers\StatsHelper;
use App\Models\BallInPlay;
use App\Models\Game;
use App\Models\Play;
use App\Models\Player;
use App\Models\Team;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GameController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create(Request $request)
    {
        return view('game.create', [
            'teams' => Team::when($request->input('season'), fn ($q) => $q->whereSeason($request->input('season')))->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $game = new Game();
        $game->home_team()->associate(Team::find($request->input('home')));
        $game->away_team()->associate(Team::find($request->input('away')));
        $game->fill($request->input());
        $game->firstPitch = Carbon::createFromFormat('Y-m-d\TH:i', $request->input('firstPitch'), $request->input('timezone'))->setTimezone('UTC');
        $game->save();
        return new JsonResponse(['status' => 'success', 'created' => $game->id]);
    }

    public function play(Game $game, Request $request) {
        if ($game->locked) {
            throw new Exception('Cannot update locked game.');
        }
        $game->state === 'decoded';
        if ($request->header('Content-Type') === 'text/plain') {
            $play = new Play(['play' => $request->getContent()]);
            $json = true;
        } else {
            $play = new Play(['play' => $request->input('play')]);
            $json = $request->accepts('application/json');
        }
        try {
            $play->apply($game);
        } catch (Exception $e) {
            if ($json) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'stackTrace' => $e->getTraceAsString(),
                ], 400);
            } else {
                return redirect()->route('game', ['game' => $game->id])->with('error', $e->getMessage());
            }
        }
        $game->state = 'force encode';
        $game->plays()->save($play);
        $game->push();
        foreach ($game->lineup as $lineup) {
            foreach ($lineup as $position) {
                foreach ($position as $player) {
                    $player->save();
                }
            }
        }
        foreach ($game->pitchers as $pitchers) {
            foreach ($pitchers as $player) {
                $player->save();
            }
        }

        $gs = new GameState;
        $state = json_decode($gs->set($game, '', '', []), true);
        $play->load('ballInPlay');
        $stats = Player::getEventStats();
        GameUpdated::dispatch($game->id, [...$play->toArray(), 'actions' => $play->actions], $state, $stats);
        if ($json) {
            return new JsonResponse(['status' => 'success', 'state' => $state, 'play' => $play, 'stats' => $stats]);
        } else {
            return redirect()->route('game', ['game' => $game->id]);
        }
    }

    public function plays(Game $game, Request $request) {
        if ($game->locked) {
            throw new Exception('Cannot update locked game.');
        }
        if ($request->header('Content-Type') === 'text/plain') {
            $plays = $request->getContent();
        } else {
            $plays = $request->input('plays');
        }
        $game->plays()->delete();
        $plays = collect(preg_split("/\n/", $plays))
            ->filter(fn (string $play) => trim($play))
            ->map(fn (string $play) => new Play(['play' => $play]));
        $gs = new GameState;
        $gs->get($game, 'state', '{}', []);
        $game->players->each(function ($player) {
            $player->stats = [];
        });

        foreach ($plays as $k => $play) {
            try {
                if (str_starts_with($play->play, 'Game Over')) {
                    $game->inning = $plays[$k - 1]->inning ?? $game->inning;
                    $game->half = $plays[$k - 1]->inning_half ?? $game->half;
                    $game->ended = true;
                }
                $play->apply($game);
            } catch (Exception $e) {
                Log::error("Error with line $k: {$play->play}");
                return response([
                    'status' => 'error',
                    'stackTrace' => $e->getTraceAsString(),
                    'message' => $e->getMessage(),
                    'line' => $k + 1
                ], 400);
            }
        }
        $game->state = 'force encode';
        $game->plays()->saveMany($plays);
        $game->push();
        foreach ($game->lineup as $lineup) {
            foreach ($lineup as $position) {
                foreach ($position as $player) {
                    $player->save();
                }
            }
        }
        foreach ($game->pitchers as $pitchers) {
            foreach ($pitchers as $player) {
                $player->save();
            }
        }
        $game->players->filter(function ($p) {
            Log::info("Final stats for player {$p->id}: " . json_encode($p->stats));
            return empty($p->stats);
        })->each(fn ($p) => $p->delete());
        GameUpdated::dispatch($game->id, null, null, null, true);
        $gs = new GameState;
        return new JsonResponse(['status' => 'success', 'state' => json_decode($gs->set($game, '', '', []), true)]);
    }

    public function undoLastPlay(Game $game) {
        if ($game->locked) {
            throw new Exception('Cannot update locked game.');
        }
        $lastPlay = $game->plays()->orderByDesc('id')->first();
        if (!$lastPlay) {
            throw new Exception('No plays to undo.');
        }
        // Delete the last play
        $lastPlay->delete();

        // Force loading of state, which should load all the players.
        $state = $game->state;

        // Reset the game state.
        $gs = new GameState;
        $gs->get($game, 'state', '{}', []);
        $plays = $game->plays()->get();

        foreach ($plays as $play) {
            $play->apply($game);
            $playLog = $play->human;
        }
        $game->state = 'force encode';
        $game->push();
        foreach ($game->lineup as $lineup) {
            foreach ($lineup as $position) {
                foreach ($position as $player) {
                    $player->save();
                }
            }
        }
        foreach ($game->pitchers as $pitchers) {
            foreach ($pitchers as $player) {
                $player->save();
            }
        }
        GameUpdated::dispatch($game->id, null, null, null, true);
        $gs = new GameState;
        return new JsonResponse([
            'status' => 'success',
            'state' => json_decode($gs->set($game, '', '', []), true),
            'play' => $play,
            'stats' => Player::getEventStats(),
        ]);
    }

    private function ballsInPlay(Game $game) {
        $game->ballsInPlay = collect();
        // TODO: Fix this, it breaks for unitizialized games.
        return;
        // Load the balls in play, for the current hitter and the last play.
        if ($game->hitting()) {
            $game->ballsInPlay = BallInPlay::whereRelation('player', 'id', $game->hitting()->id)->get();
        }
        $lastBallInPlay = BallInPlay::whereRelation('play', 'id', )->first();
        if ($lastBallInPlay) {
            $lastBallInPlay->lastPlay = true;
            $game->ballsInPlay[] = $lastBallInPlay;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Game  $game
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Game $game)
    {
        // Force load.
        $state = $game->state;
        $this->ballsInPlay($game);
        if ($game->locked) {
            return view('game.view', ['game' => $game]);
        }
        return view('game.show', ['game' => $game]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Game  $game
     * @return \Illuminate\Contracts\View\View
     */
    public function view(Game $game)
    {
        // Force load.
        $state = $game->state;
        $this->ballsInPlay($game);
        $game->locked = true;
        return view('game.view', ['game' => $game]);
    }

    /**
     * Display the touch screen scoring interface.
     *
     * @param  \App\Models\Game  $game
     * @return \Illuminate\Contracts\View\View
     */
    public function score(Game $game)
    {
        // Force load.
        $state = $game->state;
        $this->ballsInPlay($game);
        if ($game->locked) {
            return view('game.view', ['game' => $game]);
        }
        $game->load('players.person');
        return view('game.score', [
            'game' => $game,
            'state' => $game->getRawOriginal('state'),
            'lastPlay' => $game->plays()->orderByDesc('id')->first(),
        ]);
    }

    public function get(Game $game) {
        $gs = new GameState;
        $state = $game->state;
        $stats = [
            'home' => new StatsHelper([]),
            'away' => new StatsHelper([]),
        ];
        $game->load('home_team.players.person', 'away_team.players.person');
        foreach ($game->players as $player) {
            $helper = new StatsHelper($player->stats);
            $helper->derive();
            $stats[$player->id] = $helper->toArray();
            $team = $player->team_id === $game->home ? 'home' : 'away';
            $stats[$team]->merge($player->stats);
        }
        $game->load('plays');
        $stats['home'] = $stats['home']->derive()->toArray();
        $stats['away'] = $stats['away']->derive()->toArray();

        // Set cache headers to cache for 30 seconds if game is ongoing, otherwise 5 minutes.
        return response()->json([
            'game' => $game,
            'state' => json_decode($gs->set($game, '', '', []), true),
            'stats' => $stats,
        ])->header('Cache-Control', 'public, max-age=' . ($game->ended ? 300 : 30));
    }

    public function boxscore(Game $game) {
        $gs = new GameState;
        $state = $game->state;

        $stats = [];
        foreach ($game->players as $player) {
            $id = $player->person->id;
            $stats[$id] = new StatsHelper($player->stats);
            $stats[$id]->derive();
        }
        $teams = [$game->away_team, $game->home_team];
        foreach ($teams as $team) {
            $team->totals = new StatsHelper([]);
            $team->ballsInPlay = BallInPlay::whereIn('player_id',
                $game->players()->whereTeamId($team->id)->pluck('id')
            )->get();
            $game->players()->whereTeamId($team->id)->each(fn (Player $player) => $team->totals->merge($player->stats));
            $team->totals->derive();

        }

        return view('game.boxscore', [
            'game' => $game,
            'people' => $game->players->pluck('person')->unique('id')->keyBy('id'),
            'stats' => $stats,
            'teams' => $teams,
            'state' => json_decode($gs->set($game, '', '', []), true),
        ]);
    }
}
