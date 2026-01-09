@extends('layouts.main')
@section('title')
{{ $person->firstName }} {{ $person->lastName }} ({{ $team->season }})
@endsection

@section('head')
<style>
.team-page {
  --team-primary: {{ $team->primary_color ?: '#1e88ea' }};
  --team-secondary: {{ $team->secondary_color ?: '#ffffff' }};
}

.team-page .welcome-title {
  background: linear-gradient(135deg, var(--team-primary) 0%, var(--team-secondary) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  text-shadow: none;
}

.team-page .section-title {
  border-bottom: 3px solid var(--team-primary);
}

.team-page .inline-link {
  color: var(--team-primary);
}

.team-page .inline-link:hover {
  color: var(--team-primary);
  opacity: 0.8;
}

.team-page table.stats-table thead td, .team-page table.stats-table tfoot td {
    background-color: var(--team-primary);
    color: var(--white);
}

</style>
@endsection

@section('content')
<div class="welcome-container team-page">
    <h1 class="welcome-title">{{ $person->firstName }} {{ $person->lastName }} ({{ $team->season->name }})</h1>

    <a href="{{ route('person.show', ['person' => $person->id]) }}" class="inline-link">&larr; All Seasons</a><br/>
    <a href="{{ route('team', ['team' => $team->id]) }}" class="inline-link">&larr; Team Stats</a>

    <section class="section-spacing">
        <h2 class="section-title stats">Statistics</h2>

        <div class="stats-section">
            <div class="stats-card">
                <h3 class="stats-card-title">Hitting</h3>
                <div class="stats-table-container">
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
                </div>
            </div>
        </div>
    </section>

    @if ($totals->Pitches)
    <section class="section-spacing">
        <div class="stats-section">
            <div class="stats-card">
                <h3 class="stats-card-title">Pitching</h3>
                <div class="stats-table-container">
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
                </div>
            </div>
        </div>
    </section>

    <x-run-origins-chart :id="'runDistributionChart'" :walks="$totals->stat('RA.W')" :hits="$totals->stat('RA.H')" :errors="$totals->stat('RA.E')" />
    @endif

    <section class="section-spacing">
        <div class="stats-section">
            <div class="stats-card">
                <h3 class="stats-card-title">Balls in Play at Position</h3>
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
            </div>
        </div>
    </section>
</div>

@endsection