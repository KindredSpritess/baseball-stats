@extends('layouts.main')
@section('title')
{{ $team->name }}
@endsection

@section('content')
<h1>{{ $team->name }} - {{ $team->season }}</h1>

<h2>Statistics</h2>
<h3>Hitting</h3>
<table class="sortable stats-table">
    <x-hitting-stat-header />
    @foreach ($people as $person)
        <x-hitting-stat-line header="{{ strtoupper($person->lastName) }}, {{ $person->firstName }}" :stats="$stats[$person->id]" :link="route('person.show', ['person' => $person->id])" />
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
    @foreach ($people as $person)
        <x-fielding-stat-line header="{{ strtoupper($person->lastName) }}, {{ $person->firstName }}" :stats="$stats[$person->id]" :link="route('person.show', ['person' => $person->id])" />
    @endforeach
    <tfoot>
        <x-fielding-stat-line header="Totals" :stats="$totals" />
    </tfoot>
</table>

<h3>Pitching</h3>
<table class="sortable stats-table">
    <x-pitching-stat-header />
    @foreach ($people as $person)
        @if ($stats[$person->id]->IP)
            <x-pitching-stat-line header="{{ strtoupper($person->lastName) }}, {{ $person->firstName }}" :stats="$stats[$person->id]" :link="route('person.show', ['person' => $person->id])" />
        @endif
    @endforeach
    <tfoot>
        <x-pitching-stat-line header="Totals" :stats="$totals" />
    </tfoot>
</table>
</body>