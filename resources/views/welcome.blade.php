@extends('layouts.main')
@section('title')
Welcome
@endsection

@section('content')
<div x-data="{ selectedSeason: null }">
<h1>Welcome</h1>
<select x-model="selectedSeason">
    <option value="">Select a season</option>
    @foreach ($seasons as $season)
        <option value="{{ $season }}">{{ $season }}</option>
    @endforeach
</select>
@foreach ($seasons as $season)
    <div x-show="selectedSeason === '{{ $season }}'">
        <h2>{{ $season }}</h2>
        <h3>Games</h3>
        <ul>
        @foreach ($games as $game)
            @if ($game->home_team->season === $season)
                <li><a href="{{ route('game.view', ['game' => $game->id]) }}">{{ $game->away_team->name }} @ {{ $game->home_team->name }}</a> ({{ $game->firstPitch }})</li>
            @endif
        @endforeach
            <li><a href="{{ route('game.create', ['season' => $season]) }}">New Game</a></li>
        </ul>
        <h3>Teams</h3>
        <ul>
        @foreach ($teams as $team)
            @if ($team->season === $season)
                <li><a href="{{ route('team', ['team' => $team->id]) }}">{{ $team->name }}</a></li>
            @endif
        @endforeach
        </ul>
    </div>
@endforeach
</div>
@endsection