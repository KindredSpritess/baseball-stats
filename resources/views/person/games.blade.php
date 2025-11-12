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
        @if ($game->home == $team->id)
            <x-hitting-stat-line header="{{ $game->away_team->name }}" :stats="$stats[$game->id]" :link="route('game', $game->id)" sort="{{ $game->firstPitch }}" />
        @else
            <x-hitting-stat-line header="@ {{ $game->home_team->name }}" :stats="$stats[$game->id]" :link="route('game', $game->id)" sort="{{ $game->firstPitch }}" />
        @endif
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
        @foreach ($stats[$game->id]->positional() as $line)
            @if ($game->home == $team->id)
                <x-fielding-stat-line header="{{ $game->away_team->name }}" :stats="$line" :link="route('game', $game->id)" />
            @else
                <x-fielding-stat-line header="@ {{ $game->home_team->name }}" :stats="$line" :link="route('game', $game->id)" />
            @endif
        @endforeach
        @continue(count($stats[$game->id]->positional()) < 2)
        @if ($game->home == $team->id)
            <x-fielding-stat-line header="{{ $game->away_team->name }}" :stats="$stats[$game->id]" :link="route('game', $game->id)" sort="{{ $game->firstPitch }}" :hidePosition="true" />
        @else
            <x-fielding-stat-line header="@ {{ $game->home_team->name }}" :stats="$stats[$game->id]" :link="route('game', $game->id)" sort="{{ $game->firstPitch }}" :hidePosition="true" />
        @endif
    @endforeach
    <tfoot>
        @foreach ($totals->positional() as $line)
            <x-fielding-stat-line header="" :stats="$line" />
        @endforeach
        <x-fielding-stat-line header="Totals" :stats="$totals" :hidePosition="true" />
    </tfoot>
</table>


<h3>Pitching</h3>
<table class="sortable stats-table">
    <x-pitching-stat-header />
    @foreach ($games as $game)
        @if ($stats[$game->id]->Pitches)
            @if ($game->home == $team->id)
                <x-pitching-stat-line header="{{ $game->away_team->name }}" :stats="$stats[$game->id]" :link="route('game', $game->id)" sort="{{ $game->firstPitch }}" />
            @else
                <x-pitching-stat-line header="@ {{ $game->home_team->name }}" :stats="$stats[$game->id]" :link="route('game', $game->id)" sort="{{ $game->firstPitch }}" />
            @endif
        @endif
    @endforeach
    <tfoot>
        <x-pitching-stat-line header="Totals" :stats="$totals" />
    </tfoot>
</table>

<x-run-origins-chart :id="'runDistributionChart'" :walks="$totals->stat('RA.W')" :hits="$totals->stat('RA.H')" :errors="$totals->stat('RA.E')" />

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