@extends('layouts.main')
@section('title')
{{ $person->firstName }} {{ $person->lastName }}
@endsection

@section('content')
<div class="welcome-container">
    <h1 class="welcome-title">{{ $person->firstName }} {{ $person->lastName }}</h1>

    <section class="section-spacing">
        <h2 class="section-title stats">Statistics</h2>

        <div class="stats-section">
            <div class="stats-card">
                <h3 class="stats-card-title">Hitting</h3>
                <div class="stats-table-container">
                    <table class="sortable stats-table">
                        <x-hitting-stat-header />
                        @foreach ($teams as $team)
                            <x-hitting-stat-line header="{{ $team->name }} - {{ $team->season?->name }}" :stats="$stats[$team->id]" :link="route('person.games', ['person' => $person->id, 'team' => $team->id])" />
                        @endforeach
                        <tfoot>
                            <x-hitting-stat-line header="Totals" :stats="$totals" />
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section class="section-spacing">
        <h2 class="section-title charts">Spray Chart</h2>
        <div class="charts-container">
            <div class="chart-card">
                <x-field :ballsInPlay="$ballsInPlay" />
            </div>
        </div>
    </section>

    <section class="section-spacing">
        <div class="stats-section">
            <div class="stats-card">
                <h3 class="stats-card-title">Fielding</h3>
                <div class="stats-table-container">
                    <table class="sortable stats-table">
                        <x-fielding-stat-header />
                        @foreach ($teams as $team)
                            @foreach ($stats[$team->id]->positional() as $line)
                                <x-fielding-stat-line header="{{ $team->name }} - {{ $team->season?->name }}" :stats="$line" :link="route('person.games', ['person' => $person->id, 'team' => $team->id])" />
                            @endforeach
                            @continue(count($stats[$team->id]->positional()) < 2)
                            <x-fielding-stat-line header="{{ $team->name }} - {{ $team->season?->name }}" :stats="$stats[$team->id]" :link="route('person.games', ['person' => $person->id, 'team' => $team->id])" :hidePosition="true" />
                        @endforeach
                        <tfoot>
                            @foreach ($totals->positional() as $line)
                                <x-fielding-stat-line header="" :stats="$line" />
                            @endforeach
                            <x-fielding-stat-line header="Totals" :stats="$totals" :hidePosition="true" />
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section class="section-spacing">
        <div class="stats-section">
            <div class="stats-card">
                <h3 class="stats-card-title">Pitching</h3>
                <div class="stats-table-container">
                    <table class="sortable stats-table">
                        <x-pitching-stat-header />
                        @foreach ($teams as $team)
                            @if ($stats[$team->id]->IP)
                                <x-pitching-stat-line header="{{ $team->name }} - {{ $team->season?->name }}" :stats="$stats[$team->id]" :link="route('person.games', ['person' => $person->id, 'team' => $team->id])" />
                            @endif
                        @endforeach
                        <tfoot>
                            <x-pitching-stat-line header="Totals" :stats="$totals" />
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection