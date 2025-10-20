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
    @foreach ($people->sortByDesc(fn($person) => $stats[$person->id]->OPS) as $person)
        <x-hitting-stat-line header="{{ $person->firstName }} {{ $person->lastName }}" :stats="$stats[$person->id]" :link="route('person.show', ['person' => $person->id])" />
    @endforeach
    <tfoot>
        <x-hitting-stat-line header="Totals" :stats="$totals" />
    </tfoot>
</table>

<h4 onclick="$('.balls-in-play').toggle()" style="text-decoration:underline dotted;cursor:pointer">Spray Charts</h4>
<div class='balls-in-play' style="display:none;">
@foreach ($people->sortBy(fn($person) => $person->lastName) as $person)
    <div class='position'>
        <h5>{{ strtoupper($person->lastName) }}, {{ $person->firstName }}</h5>
        <x-field :ballsInPlay="$ballsInPlay[$person->id] ?? []" />
    </div>
    @endforeach
</div>

<h3>Fielding</h3>
<table class="sortable stats-table">
    <x-fielding-stat-header />
    @foreach ($people->sortByDesc(fn($person) => $stats[$person->id]->TC) as $person)
        <x-fielding-stat-line header="{{ $person->firstName }} {{ $person->lastName }}" :stats="$stats[$person->id]" :link="route('person.show', ['person' => $person->id])" />
    @endforeach
    <tfoot>
        <x-fielding-stat-line header="Totals" :stats="$totals" />
    </tfoot>
</table>

<h3>Pitching</h3>
<table class="sortable stats-table">
    <x-pitching-stat-header />
    @foreach ($people->filter(fn($person) => $stats[$person->id]->IP)->sortByDesc(fn($person) => $stats[$person->id]->IP) as $person)
        <x-pitching-stat-line header="{{ $person->firstName }} {{ $person->lastName }}" :stats="$stats[$person->id]" :link="route('person.show', ['person' => $person->id])" />
    @endforeach
    <tfoot>
        <x-pitching-stat-line header="Totals" :stats="$totals" />
    </tfoot>
</table>
</body>