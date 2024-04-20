@extends('layouts.main')
@section('title')
{{ $person->firstName }} {{ $person->lastName }}
@endsection

@section('content')
<h1>{{ $person->firstName }} {{ $person->lastName }}</h1>

<h2>Statistics</h2>
<h3>Hitting</h3>
<table class="sortable stats-table">
    <x-hitting-stat-header />
@foreach ($teams as $team)
    <x-hitting-stat-line header="{{ $team->name }} - {{ $team->season }}" :stats="$stats[$team->id]" :link="route('person.games', ['person' => $person->id, 'team' => $team->id])" />
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
@foreach ($teams as $team)
    @foreach ($stats[$team->id]->positional() as $line)
        <x-fielding-stat-line header="{{ $team->name }} - {{ $team->season }}" :stats="$line" :link="route('person.games', ['person' => $person->id, 'team' => $team->id])" />
    @endforeach
    @continue(count($stats[$team->id]->positional()) < 2)
    <x-fielding-stat-line header="{{ $team->name }} - {{ $team->season }}" :stats="$stats[$team->id]" :link="route('person.games', ['person' => $person->id, 'team' => $team->id])" :hidePosition="true" />
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
@foreach ($teams as $team)
    @if ($stats[$team->id]->IP)
        <x-pitching-stat-line header="{{ $team->name }} - {{ $team->season }}" :stats="$stats[$team->id]" :link="route('person.games', ['person' => $person->id, 'team' => $team->id])" />
    @endif
@endforeach
    <tfoot>
        <x-pitching-stat-line header="Totals" :stats="$totals" />
    </tfoot>
</table>
@endsection