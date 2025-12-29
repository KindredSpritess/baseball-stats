@extends('layouts.main')
@section('title')
{{ $game->home_team->name }} vs {{ $game->away_team->name }} - Box Score
@endsection

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">{{ $game->away_team->name }} @ {{ $game->home_team->name }}</h1>
        <p class="page-subtitle">{{ $game->firstPitch->format('F j, Y \a\t g:i A') }} - Box Score</p>
    </div>

    @for ($home = 0; $home <= 1; $home++)
    <div class="stats-section">
        <h2 class="stats-section-title">{{ $teams[$home]->name }}</h2>

        <h3 class="stats-subsection-title">Hitting</h3>
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

        <div class="chart-container">
            <h4>Spray Chart</h4>
            <x-field :ballsInPlay="$teams[$home]->ballsInPlay" />
        </div>

        <h3 class="stats-subsection-title">Fielding</h3>
        <table class="sortable stats-table">
            <x-fielding-stat-header singleGameStats="true" />
            @foreach ($game->players()->whereTeamId($teams[$home]->id)->get() as $player)
                <x-fielding-stat-line header="{{ $player->person->firstName }} {{ $player->person->lastName }}" :stats="$stats[$player->person->id]" :link="route('person.show', ['person' => $player->person->id])" singleGameStats="true" />
            @endforeach
            <tfoot>
                <x-fielding-stat-line header="Totals" :stats="$teams[$home]->totals" :hidePosition="true" singleGameStats="true" />
            </tfoot>
        </table>

        <h3 class="stats-subsection-title">Pitching</h3>
        <table class="sortable stats-table">
            <x-pitching-stat-header singleGameStats="true" />
            @foreach ($game->pitchers[$home] as $player)
                <x-pitching-stat-line header="{{ $player->person->firstName }} {{ $player->person->lastName }}" :stats="$stats[$player->person->id]" :link="route('person.show', ['person' => $player->person->id])" singleGameStats="true" />
            @endforeach
            <tfoot>
                <x-pitching-stat-line header="Totals" :stats="$teams[$home]->totals" singleGameStats="true" />
            </tfoot>
        </table>

        <div class="chart-container">
            <h4>Run Origins Chart</h4>
            <x-run-origins-chart :id="'runDistributionChart.' . $home" :walks="$teams[$home]->totals->stat('RA.W')" :hits="$teams[$home]->totals->stat('RA.H')" :errors="$teams[$home]->totals->stat('RA.E')" />
        </div>

        <div class="chart-container">
            <h4>Runs Allowed Chart</h4>
            <div id="onbaseDistributionChart.{{ $home }}" class="pie-chart"></div>
            <script>
                google.charts.load('current', {'packages':['sankey']});
                google.charts.setOnLoadCallback(() => {
                    const data = new google.visualization.DataTable();
                    data.addColumn('string', 'How Reached');
                    data.addColumn('string', 'Result');
                    data.addColumn('number');
                    data.addRows([
                        ['Walks / HBP', 'Scored', {{ $teams[$home]->totals->stat('RA.W') }}],
                        ['Walks / HBP', 'Did Not Score', {{ $teams[$home]->totals->stat('BB') + $teams[$home]->totals->stat('HBP') - $teams[$home]->totals->stat('RA.W') }}],
                        ['Hits', 'Scored', {{ $teams[$home]->totals->stat('RA.H') }}],
                        ['Hits', 'Did Not Score', {{ $teams[$home]->totals->stat('HA') - $teams[$home]->totals->stat('RA.H') }}],
                        ['Errors', 'Scored', {{ $teams[$home]->totals->stat('RA.E') }}],
                        ['Errors', 'Did Not Score', {{ $teams[$home]->totals->stat('ABOE') - $teams[$home]->totals->stat('RA.E') }}]
                    ]);
                    const options = {
                        title: 'Runs Allowed Chart',
                        sankey: {
                            node: {
                                colors: [ '#2b5797', '#1e7145', '#eeeeee', '#1e7145', '#FFCE56', ],
                            }
                        }
                    };
                    const chart = new google.visualization.Sankey(document.getElementById('onbaseDistributionChart.{{ $home }}'));
                    chart.draw(data, options);
                });
            </script>
        </div>
    </div>
    @endfor
</div>
@endsection