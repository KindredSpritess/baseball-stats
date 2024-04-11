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
        @if ($game->home == $team->id)
            <x-fielding-stat-line header="{{ $game->away_team->name }}" :stats="$stats[$game->id]" :link="route('game', $game->id)" sort="{{ $game->firstPitch }}" />
        @else
            <x-fielding-stat-line header="@ {{ $game->home_team->name }}" :stats="$stats[$game->id]" :link="route('game', $game->id)" sort="{{ $game->firstPitch }}" />
        @endif
    @endforeach
    <tfoot>
        <x-fielding-stat-line header="Totals" :stats="$totals" />
    </tfoot>
</table>


<h3>Pitching</h3>
<table class="sortable stats-table">
    <x-pitching-stat-header />
    @foreach ($games as $game)
        @if ($stats[$game->id]->IP)
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
@endsection