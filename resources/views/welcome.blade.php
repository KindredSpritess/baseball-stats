@extends('layouts.main')
@section('title')
Welcome
@endsection

@section('content')
@php
$now = now();
$threeMonthsAgo = $now->copy()->subMonths(3);
$inProgressGames = collect($games)->filter(function($game) use ($now) {
    return !$game->ended && $game->firstPitch < $now->subHours(2);
});
$games->each(function($game) {
    // Load state.
    $value = $game->state;
});
$recentSeasons = collect($seasons)->filter(function($season) use ($games, $threeMonthsAgo) {
    return collect($games)->some(function($game) use ($season, $threeMonthsAgo) {
        return $game->home_team->season === $season && $game->firstPitch > $threeMonthsAgo;
    });
});
@endphp
<div x-data="{
    selectedSeason: localStorage.getItem('selected_season') || null,
    init() {
        this.$watch('selectedSeason', (value) => {
            localStorage.setItem('selected_season', value);
        });
    }
}" style="max-width: 1200px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh;">
    <h1 style="text-align: center; font-size: 2.5em; color: #2c3e50; margin-bottom: 30px;">Welcome to Baseball Stats</h1>

    @if(auth()->check())
    <p style="text-align: center; font-size: 1.2em; color: #34495e; margin-bottom: 20px;">Welcome back, {{ auth()->user()->name }}!</p>
    @endif

    @if($inProgressGames->count() > 0)
    <section style="margin-bottom: 40px;">
        <h2 style="font-size: 1.8em; color: #27ae60; margin-bottom: 20px; border-bottom: 2px solid #27ae60; padding-bottom: 10px;">In Progress Games</h2>
        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
            @foreach($inProgressGames as $game)
            <div style="background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; flex: 1 1 300px; border-left: 4px solid #27ae60;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="font-weight: bold; color: #2c3e50;">{{ $game->away_team->name }}</span>
                    <span style="font-weight: bold; color: #e74c3c;">{{ $game->score[0] }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                    <span style="font-weight: bold; color: #2c3e50;">{{ $game->home_team->name }}</span>
                    <span style="font-weight: bold; color: #e74c3c;">{{ $game->score[1] }}</span>
                </div>
                <p style="margin: 0 0 15px 0; color: #7f8c8d; font-size: 0.9em;">{{ $game->firstPitch->format('M j, Y g:i A') }} - {{ $game->half ? 'Bottom' : 'Top' }} {{ $game->inning }}</p>
                <a href="{{ route('game.view', ['game' => $game->id]) }}" style="color: #3498db; text-decoration: none; font-weight: bold;">View Game →</a>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    <section style="margin-bottom: 40px;">
        <h2 style="font-size: 1.8em; color: #8e44ad; margin-bottom: 20px; border-bottom: 2px solid #8e44ad; padding-bottom: 10px;">Current Seasons</h2>
        @if($recentSeasons->count() > 0)
        <div style="display: flex; flex-wrap: wrap; gap: 15px;">
            @foreach($recentSeasons as $season)
            <div style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%); color: white; border-radius: 8px; padding: 20px; cursor: pointer; flex: 1 1 200px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);" @click="selectedSeason = '{{ $season }}'">
                <h3 style="margin: 0 0 10px 0; font-size: 1.5em;">{{ $season }}</h3>
                <p style="margin: 0; font-size: 0.9em;">Active Season</p>
            </div>
            @endforeach
        </div>
        @else
        <p style="color: #7f8c8d; font-style: italic;">No recent seasons with games in the last 3 months.</p>
        @endif
    </section>

    <section>
        <h2 style="font-size: 1.8em; color: #34495e; margin-bottom: 20px; border-bottom: 2px solid #34495e; padding-bottom: 10px;">All Seasons</h2>
        <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <select x-model="selectedSeason" style="width: 100%; max-width: 400px; padding: 10px; border: 1px solid #bdc3c7; border-radius: 4px; margin-bottom: 20px; display: block; margin-left: auto; margin-right: auto;">
                <option value="">Select a season</option>
                @foreach ($seasons as $season)
                    <option value="{{ $season }}">{{ $season }}</option>
                @endforeach
            </select>

            @foreach ($seasons as $season)
            <div x-show="selectedSeason === '{{ $season }}'" style="margin-top: 20px;">
                <h3 style="font-size: 1.5em; color: #2c3e50; margin-bottom: 20px;">{{ $season }}</h3>
                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                    <div style="flex: 1 1 45%; min-width: 300px;">
                        <h4 style="color: #3498db; margin-bottom: 10px;">Games</h4>
                        <ul style="list-style: none; padding: 0;">
                            @foreach ($games as $game)
                                @if ($game->home_team->season === $season)
                                <li style="margin-bottom: 8px;">
                                    @if($game->ended)
                                        @php $awayScore = $game->score[0]; $homeScore = $game->score[1]; @endphp
                                        <a href="{{ route('game.view', ['game' => $game->id]) }}" style="color: #3498db; text-decoration: none;">
                                            @if($awayScore > $homeScore)
                                                <strong>{{ $game->away_team->name }} {{ $awayScore }}</strong> @ {{ $game->home_team->name }} {{ $homeScore }}
                                            @elseif($homeScore > $awayScore)
                                                {{ $game->away_team->name }} {{ $awayScore }} @ <strong>{{ $game->home_team->name }} {{ $homeScore }}</strong>
                                            @else
                                                {{ $game->away_team->name }} {{ $awayScore }} @ {{ $game->home_team->name }} {{ $homeScore }}
                                            @endif
                                        </a>
                                        <span style="color: #7f8c8d; font-size: 0.9em;">({{ $game->firstPitch->format('M j, Y') }})</span>
                                    @else
                                        <a href="{{ route('game.view', ['game' => $game->id]) }}" style="color: #3498db; text-decoration: none;">{{ $game->away_team->name }} @ {{ $game->home_team->name }}</a>
                                        <span style="color: #7f8c8d; font-size: 0.9em;">({{ $game->firstPitch->format('M j, Y g:i A') }})</span>
                                    @endif
                                </li>
                                @endif
                            @endforeach
                            @can('create-game')
                            <li style="margin-top: 10px;">
                                <a href="{{ route('game.create', ['season' => $season]) }}" style="color: #27ae60; text-decoration: none; font-weight: bold;">+ New Game</a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                    <div style="flex: 1 1 45%; min-width: 300px;">
                        <h4 style="color: #e67e22; margin-bottom: 10px;">Teams</h4>
                        <ul style="list-style: none; padding: 0;">
                            @foreach ($teams as $team)
                                @if ($team->season === $season)
                                <li style="margin-bottom: 8px;">
                                    <a href="{{ route('team', ['team' => $team->id]) }}" style="color: #e67e22; text-decoration: none;">{{ $team->name }}</a>
                                </li>
                                @endif
                            @endforeach
                            <li style="margin-bottom: 8px;">
                                <a href="{{ route('stats.show', ['seasons' => [$season]]) }}" style="color: #e67e22; text-decoration: none;">All Teams</a>
                            </li>
                            @can('create-team')
                            <li style="margin-top: 10px;">
                                <a href="{{ route('team.create', ['season' => $season]) }}" style="color: #27ae60; text-decoration: none; font-weight: bold;">+ New Team</a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </section>
</div>
@endsection