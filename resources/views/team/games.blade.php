@extends('layouts.main')
@section('title')
{{ $team->name }} - Game Log
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

    <a href="{{ route('team', ['team' => $team->id]) }}" class="inline-link">&larr; Team Stats</a>

    <section class="section-spacing">
        <h2 class="section-title stats">Hitting - By Game</h2>

        <div class="stats-section">
            <div class="stats-card">
                <div class="stats-table-container">
                    <table class="sortable stats-table">
                        <x-hitting-stat-header />
                        @foreach ($games as $game)
                            @php
                                $opponent = $game->home == $team->id ? $game->away_team->name : '@ ' . $game->home_team->name;
                            @endphp
                            <x-hitting-stat-line header="{{ $opponent }}" :stats="$stats[$game->id]" :link="route('game.boxscore', $game->id)" sort="{{ $game->firstPitch }}" />
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
        <h2 onclick="$('#balls-in-play').toggle()" class="section-title spray spray-toggle">Spray Charts - By Game</h2>
        <div id="balls-in-play" class='balls-in-play' style="display:none;">
            @foreach ($games as $game)
                @php
                    $opponent = $game->home == $team->id ? $game->away_team->name : '@ ' . $game->home_team->name;
                @endphp
                <div class='position'>
                    <h5><a href="{{ route('game.boxscore', $game->id) }}" class="inline-link">{{ $opponent }}</a></h5>
                    @if ($game->firstPitch)
                        <small>{{ $game->firstPitch->format('M j, Y') }}</small>
                    @endif
                    <x-field :ballsInPlay="$ballsInPlay[$game->id] ?? []" />
                </div>
            @endforeach
        </div>
    </section>

    <section class="section-spacing">
        <div class="stats-section">
            <div class="stats-card">
                <h3 class="stats-card-title">Fielding - By Game</h3>
                <div class="stats-table-container">
                    <table class="sortable stats-table">
                        <x-fielding-stat-header />
                        @foreach ($games as $game)
                            @php
                                $opponent = $game->home == $team->id ? $game->away_team->name : '@ ' . $game->home_team->name;
                            @endphp
                            <x-fielding-stat-line header="{{ $opponent }}" :stats="$stats[$game->id]" :link="route('game.boxscore', $game->id)" sort="{{ $game->firstPitch }}" :hidePosition="true" />
                        @endforeach
                        <tfoot>
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
                <h3 class="stats-card-title">Pitching - By Game</h3>
                <div class="stats-table-container">
                    <table class="sortable stats-table">
                        <x-pitching-stat-header />
                        @foreach ($games as $game)
                            @if ($stats[$game->id]->Pitches)
                            @php
                                $opponent = $game->home == $team->id ? $game->away_team->name : '@ ' . $game->home_team->name;
                            @endphp
                                <x-pitching-stat-line header="{{ $opponent }}" :stats="$stats[$game->id]" :link="route('game.boxscore', $game->id)" sort="{{ $game->firstPitch }}" />
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
    @endif
</div>
@endsection
