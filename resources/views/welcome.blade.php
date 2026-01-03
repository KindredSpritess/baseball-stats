@extends('layouts.main')
@section('title')
Welcome
@endsection

@section('content')
@php
$now = \Carbon\CarbonImmutable::now();
$threeMonthsAgo = $now->copy()->subMonths(3);
$inProgressGames = collect($games)->filter(function($game) use ($now) {
    return !$game->ended && $game->firstPitch->lessThan($now->addHours(4));
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
<div class="welcome-container" x-data="{
    selectedSeason: localStorage.getItem('selected_season') || null,
    init() {
        this.$watch('selectedSeason', (value) => {
            localStorage.setItem('selected_season', value);
        });
    }
}">
    <h1 class="welcome-title">Welcome to Baseball Stats</h1>

    @if(auth()->check())
    <p class="welcome-subtitle">Welcome back, {{ auth()->user()->name }}!</p>
    @endif

    @if($inProgressGames->count() > 0)
    <section class="section-spacing">
        <h2 class="section-title in-progress">In Progress Games</h2>
        <div class="games-grid">
            @foreach($inProgressGames as $game)
            <div class="game-card">
                <div class="game-team">
                    <span class="game-team away">{{ $game->away_team->name }}</span>
                    <span class="game-score">{{ $game->score[0] }}</span>
                </div>
                <div class="game-team">
                    <span class="game-team home">{{ $game->home_team->name }}</span>
                    <span class="game-score">{{ $game->score[1] }}</span>
                </div>
                <p class="game-details"><span class="local-time" data-utc="{{ $game->firstPitch->toISOString() }}">{{ $game->firstPitch->format('M j, Y g:i A') }}</span> - {{ $game->half ? 'Bottom' : 'Top' }} {{ $game->inning }}</p>
                <a href="{{ route('game.view', ['game' => $game->id]) }}" class="game-link">View Game →</a>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    <section class="section-spacing">
        <h2 class="section-title current-seasons">Current Seasons</h2>
        @if($recentSeasons->count() > 0)
        <div class="seasons-grid">
            @foreach($recentSeasons as $season)
            <div class="season-card" @click="selectedSeason = '{{ $season }}'">
                <h3 class="season-title">{{ $season }}</h3>
                <p class="season-description">Active Season</p>
            </div>
            @endforeach
        </div>
        @else
        <p class="no-content">No recent seasons with games in the last 3 months.</p>
        @endif
    </section>

    <section>
        <h2 class="section-title all-seasons">All Seasons</h2>
        <div class="seasons-container">
            <select class="season-select" x-model="selectedSeason">
                <option value="">Select a season</option>
                @foreach ($seasons as $season)
                    <option value="{{ $season }}">{{ $season }}</option>
                @endforeach
            </select>

            @foreach ($seasons as $season)
            <div x-show="selectedSeason === '{{ $season }}'" class="season-content">
                <h3 class="season-content-title">{{ $season }}</h3>
                <div class="games-list">
                    <div class="games-column">
                        <h4>Games</h4>
                        <ul class="games-list-items">
                            @foreach ($games as $game)
                                @if ($game->home_team->season === $season)
                                <li class="game-item">
                                    @if($game->ended)
                                        @php $awayScore = $game->score[0]; $homeScore = $game->score[1]; @endphp
                                        <a href="{{ route('game.view', ['game' => $game->id]) }}">
                                            @if($awayScore > $homeScore)
                                                <span class="game-winner">{{ $game->away_team->name }} {{ $awayScore }}</span> @ {{ $game->home_team->name }} {{ $homeScore }}
                                            @elseif($homeScore > $awayScore)
                                                {{ $game->away_team->name }} {{ $awayScore }} @ <span class="game-winner">{{ $game->home_team->name }} {{ $homeScore }}</span>
                                            @else
                                                {{ $game->away_team->name }} {{ $awayScore }} @ {{ $game->home_team->name }} {{ $homeScore }}
                                            @endif
                                        </a>
                                        <span class="game-location">(<span class="local-time" data-utc="{{ $game->firstPitch->toISOString() }}" data-format='{"month": "short", "day": "numeric", "year": "numeric"}'>{{ $game->firstPitch->format('M j, Y') }}</span>)</span>
                                    @else
                                        <a href="{{ route('game.view', ['game' => $game->id]) }}">{{ $game->away_team->name }} @ {{ $game->home_team->name }}</a>
                                        <span class="game-location">(<span class="local-time" data-utc="{{ $game->firstPitch->toISOString() }}">{{ $game->firstPitch->format('M j, Y g:i A') }}</span>)</span>
                                        <br/>
                                        <span class="game-location">{{ $game->location }}</span>
                                    @endif
                                </li>
                                @endif
                            @endforeach
                            @can('create-game')
                            <li class="new-item">
                                <a href="{{ route('game.create', ['season' => $season]) }}">+ New Game</a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                    <div class="games-column teams">
                        <h4>Teams</h4>
                        <ul class="games-list-items">
                            @foreach ($teams as $team)
                                @if ($team->season === $season)
                                <li class="game-item teams">
                                    <a href="{{ route('team', ['team' => $team->id]) }}">{{ $team->name }}</a>
                                </li>
                                @endif
                            @endforeach
                            <li class="game-item teams">
                                <a href="{{ route('stats.show', ['seasons' => [$season]]) }}">All Teams</a>
                            </li>
                            @can('create-team')
                            <li class="new-item">
                                <a href="{{ route('team.create', ['season' => $season]) }}">+ New Team</a>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.local-time').forEach(function(el) {
        const utc = new Date(el.dataset.utc);
        el.textContent = utc.toLocaleString('en-US', JSON.parse(el.dataset.format || 'null') || { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    });
});
</script>
@endsection