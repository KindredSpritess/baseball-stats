@extends('layouts.main')
@section('title')
{{ $team->name }}
@endsection

@section('content')
<h1>{{ $team->name }} - {{ $team->season }}</h1>
<h2>Statistics - Qualified (<a href="{{ route('team', ['team' => $team->id, 'qualified' => 'all']) }}">see all</a>)</h2>
<h3>Hitting - Minimum {{ number_format($minPA, 1) }} PAs</h3>
<table class="sortable stats-table">
    <x-hitting-stat-header />
    @foreach ($people->sortByDesc(fn($person) => $stats[$person->id]->OPS) as $person)
        @if ($stats[$person->id]->PA < $minPA)
            @continue
        @endif
        <x-hitting-stat-line header="{{ $person->firstName }} {{ $person->lastName }}" :stats="$stats[$person->id]" :link="route('person.games', ['person' => $person->id, 'team' => $team])" />
    @endforeach
    <tfoot>
        <x-hitting-stat-line header="Totals" :stats="$totals" />
    </tfoot>
</table>

<x-run-origins-chart :id="'runsScoredChart'" :walks="$totals->stat('R.W')" :hits="$totals->stat('R.H')" :errors="$totals->stat('R.E')" />

<h4 onclick="$('.balls-in-play').toggle()" style="text-decoration:underline dotted;cursor:pointer">Spray Charts</h4>
<div class='balls-in-play' style="display:none;">
@foreach ($people->sortBy(fn($person) => $person->lastName) as $person)
    <div class='position'>
        <h5>{{ strtoupper($person->lastName) }}, {{ $person->firstName }}</h5>
        <x-field :ballsInPlay="$ballsInPlay[$person->id] ?? []" />
    </div>
    @endforeach
</div>

<h3>Fielding - Minimum {{ App\Helpers\StatsHelper::innings_format($minFI) }} FIs</h3>
<table class="sortable stats-table">
    <x-fielding-stat-header />
    @foreach ($people->sortByDesc(fn($person) => $stats[$person->id]->TC) as $person)
        @if ($stats[$person->id]->FI < $minFI)
            @continue
        @endif
        <x-fielding-stat-line header="{{ $person->firstName }} {{ $person->lastName }}" :stats="$stats[$person->id]" :link="route('person.games', ['person' => $person->id, 'team' => $team])" />
    @endforeach
    <tfoot>
        <x-fielding-stat-line header="Totals" :stats="$totals" />
    </tfoot>
</table>

<h3>Pitching - Minimum {{ App\Helpers\StatsHelper::innings_format($minIP, 1) }} IPs</h3>
<table class="sortable stats-table">
    <x-pitching-stat-header />
    @foreach ($people->filter(fn($person) => $stats[$person->id]->IP)->sortByDesc(fn($person) => $stats[$person->id]->IP) as $person)
        @if ($stats[$person->id]->IP < $minIP)
            @continue
        @endif
        <x-pitching-stat-line header="{{ $person->firstName }} {{ $person->lastName }}" :stats="$stats[$person->id]" :link="route('person.games', ['person' => $person->id, 'team' => $team])" />
    @endforeach
    <tfoot>
        <x-pitching-stat-line header="Totals" :stats="$totals" />
    </tfoot>
</table>

<x-run-origins-chart :id="'runsAllowedChart'" :walks="$totals->stat('RA.W')" :hits="$totals->stat('RA.H')" :errors="$totals->stat('RA.E')" />
</body>
@endsection