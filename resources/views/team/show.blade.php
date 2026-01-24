@extends('layouts.main')
@section('title')
{{ $team->name }}
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
    <h1 class="welcome-title">{{ $team->name }} - {{ $team->season?->name }}</h1>

    @can('edit-team', $team)
    <a href="{{ route('team.edit', ['team' => $team->id]) }}" class="inline-link">Edit Team Details</a>
    <a href="{{ route('roster.import', ['team' => $team->id]) }}" class="inline-link">Import Players</a>
    @endcan

    <section class="section-spacing">
        <h2 class="section-title stats">Statistics - Qualified (<a href="{{ route('team', ['team' => $team->id, 'qualified' => 'all']) }}" class="inline-link">see all</a>)</h2>

        <div class="stats-section">
            <div class="stats-card">
                <h3 class="stats-card-title">Hitting - Minimum {{ number_format($minPA, 1) }} PAs</h3>
                <div class="stats-table-container">
                    <table class="sortable stats-table">
                        <x-hitting-stat-header />
                        @php
                        /** @var App\Helpers\StatsHelper[] $stats */
                        @endphp
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
                </div>
            </div>
        </div>
    </section>

    <section class="">
        <h2 class="section-title charts">Team Analytics</h2>
        <div class="charts-container">
            <div class="chart-card">
                <x-run-origins-chart :id="'runsScoredChart'" :walks="$totals->stat('R.W')" :hits="$totals->stat('R.H')" :errors="$totals->stat('R.E')" />
            </div>
        </div>
    </section>

    <section class="section-spacing">
        <h2 onclick="$('.balls-in-play').toggle()" class="section-title spray spray-toggle">Spray Charts</h2>
        <div class='balls-in-play' style="display:none;">
            @foreach ($people->sortBy(fn($person) => $person->lastName) as $person)
                <div class='position'>
                    <h5>{{ strtoupper($person->lastName) }}, {{ $person->firstName }}</h5>
                    <x-field :ballsInPlay="$ballsInPlay[$person->id] ?? []" />
                </div>
            @endforeach
        </div>
    </section>

    <section class="section-spacing">
        <div class="stats-section">
            <div class="stats-card">
                <h3 class="stats-card-title">Fielding - Minimum {{ App\Helpers\StatsHelper::innings_format($minFI) }} FIs</h3>
                <div class="stats-table-container">
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
                </div>
            </div>
        </div>
    </section>

    <section class="section-spacing">
        <div class="stats-section">
            <div class="stats-card">
                <h3 class="stats-card-title">Pitching - Minimum {{ App\Helpers\StatsHelper::innings_format($minIP) }} IPs</h3>
                <div class="stats-table-container">
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
                </div>
            </div>
        </div>
    </section>

    <section class="section-spacing">
        <h2 class="section-title charts">Pitching Analytics</h2>
        <div class="charts-container">
            <div class="chart-card">
                <x-run-origins-chart :id="'runsAllowedChart'" :walks="$totals->stat('RA.W')" :hits="$totals->stat('RA.H')" :errors="$totals->stat('RA.E')" />
            </div>
        </div>
    </section>

    <section class="section-spacing">
        <h2 onclick="$('.balls-in-play').toggle()" class="section-title spray spray-toggle">Pitcher Spray Charts</h2>
        <div class='balls-in-play' style="display:none;">
            @foreach ($people->filter(fn($person) => $stats[$person->id]->IP)->sortBy(fn($person) => $person->lastName) as $person)
                <div class='position'>
                    <h5>{{ strtoupper($person->lastName) }}, {{ $person->firstName }}</h5>
                    <x-field :ballsInPlay="$pitchingBIP[$person->id] ?? []" />
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection