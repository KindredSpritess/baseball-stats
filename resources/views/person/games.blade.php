@extends('layouts.main')
@section('title')
{{ $person->firstName }} {{ $person->lastName }} ({{ $team->season }})
@endsection

@section('content')
<style>
    
</style>

<h1>{{ $person->firstName }} {{ $person->lastName }}</h1>

<h2>Statistics</h2>
<h3>Hitting</h3>
<table class="sortable stats-table">
    <x-hitting-stat-header />
    @foreach ($games as $game)
        @php
            $route = Gate::allows('score-game', $game) ? 'game' : 'game.view';
            $header = $game->home == $team->id ? $game->away_team->name : '@ ' . $game->home_team->name;
        @endphp
        <x-hitting-stat-line header="{{ $header }}" :stats="$stats[$game->id]" :link="route($route, $game->id)" sort="{{ $game->firstPitch }}" />
    @endforeach
    <tfoot>
        <x-hitting-stat-line header="Totals" :stats="$totals" />
    </tfoot>
</table>

<h4>Spray Chart</h4>
<x-field :ballsInPlay="$ballsInPlay" />

<h3>Fielding</h3>
<table class="sortable stats-table">
    <x-fielding-stat-header />
    @foreach ($games as $game)
        @php
            $route = Gate::allows('score-game', $game) ? 'game' : 'game.view';
            $header = $game->home == $team->id ? $game->away_team->name : '@ ' . $game->home_team->name;
        @endphp
        @foreach ($stats[$game->id]->positional() as $line)
            <x-fielding-stat-line header="{{ $header }}" :stats="$line" :link="route($route, $game->id)" />
        @endforeach
        @continue(count($stats[$game->id]->positional()) < 2)
        <x-fielding-stat-line header="{{ $header }}" :stats="$stats[$game->id]" :link="route($route, $game->id)" sort="{{ $game->firstPitch }}" :hidePosition="true" />
    @endforeach
    <tfoot>
        @foreach ($totals->positional() as $line)
            <x-fielding-stat-line header="" :stats="$line" />
        @endforeach
        <x-fielding-stat-line header="Totals" :stats="$totals" :hidePosition="true" />
    </tfoot>
</table>

@if ($totals->Pitches)
<h3>Pitching</h3>
<table class="sortable stats-table">
    <x-pitching-stat-header />
    @foreach ($games as $game)
        @if ($stats[$game->id]->Pitches)
        @php
            $route = Gate::allows('score-game', $game) ? 'game' : 'game.view';
            $header = $game->home == $team->id ? $game->away_team->name : '@ ' . $game->home_team->name;
        @endphp
            <x-pitching-stat-line header="{{ $header }}" :stats="$stats[$game->id]" :link="route($route, $game->id)" sort="{{ $game->firstPitch }}" />
        @endif
    @endforeach
    <tfoot>
        <x-pitching-stat-line header="Totals" :stats="$totals" />
    </tfoot>
</table>

<x-run-origins-chart :id="'runDistributionChart'" :walks="$totals->stat('RA.W')" :hits="$totals->stat('RA.H')" :errors="$totals->stat('RA.E')" />
@endif

<h3>Balls In Play at Position</h3>
<div class='balls-in-play'>
    @foreach ($totals->positional() as $line)
    <div class='position'>
        <object type="image/svg+xml" data="{{ route('person.inplays', [$person->id, $team->id, $line->Position]) }}">
            Your browser does not support SVG
        </object>
        <h3>{{ App\Helpers\StatsHelper::position($line->Position) }}</h3>
    </div>
    @endforeach
</div>

@endsection