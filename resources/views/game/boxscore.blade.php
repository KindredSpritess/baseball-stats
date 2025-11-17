@extends('layouts.main')
@section('title')
{{ $game->home_team->name }} - {{ $game->away_team->name }}
@endsection

@section('content')
@for ($home = 0; $home <= 1; $home++)
    <h1>{{ $teams[$home]->name }}</h1>
    <h3>Hitting</h3>
    <table class="sortable stats-table">
        <x-hitting-stat-header :singleGameStats="true" />
        @foreach ($game->lineup[$home] as $spot)
            @foreach ($spot as $player)
                <x-hitting-stat-line header="{{ $player->person->firstName }} {{ $player->person->lastName }}" :stats="$stats[$player->person->id]" :link="route('person.show', ['person' => $player->person->id])" singleGameStats="true" />
            @endforeach
        @endforeach
        <tfoot>
            <x-hitting-stat-line header="Totals" :stats="$teams[$home]->totals" singleGameStats="true" />
        </tfoot>
    </table>

    <h4>Spray Chart</h4>
    <x-field :ballsInPlay="$teams[$home]->ballsInPlay" />

    <h3>Fielding</h3>
    <table class="sortable stats-table">
        <x-fielding-stat-header singleGameStats="true" />
        @foreach ($game->players()->whereTeamId($teams[$home]->id)->get() as $player)
            <x-fielding-stat-line header="{{ $player->person->firstName }} {{ $player->person->lastName }}" :stats="$stats[$player->person->id]" :link="route('person.show', ['person' => $player->person->id])" singleGameStats="true" />
        @endforeach
        <tfoot>
            <x-fielding-stat-line header="Totals" :stats="$teams[$home]->totals" :hidePosition="true" singleGameStats="true" />
        </tfoot>
    </table>

    <h3>Pitching</h3>
    <table class="sortable stats-table">
        <x-pitching-stat-header singleGameStats="true" />
        @foreach ($game->pitchers[$home] as $player)
            <x-pitching-stat-line header="{{ $player->person->firstName }} {{ $player->person->lastName }}" :stats="$stats[$player->person->id]" :link="route('person.show', ['person' => $player->person->id])" singleGameStats="true" />
        @endforeach
        <tfoot>
            <x-pitching-stat-line header="Totals" :stats="$teams[$home]->totals" singleGameStats="true" />
        </tfoot>
    </table>

    <x-run-origins-chart :id="'runDistributionChart.' . $home" :walks="$teams[$home]->totals->stat('RA.W')" :hits="$teams[$home]->totals->stat('RA.H')" :errors="$teams[$home]->totals->stat('RA.E')" />
@endfor
</body>
@endsection