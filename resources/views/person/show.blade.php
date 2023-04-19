<head>
    <title>{{ $person->firstName }} {{ $person->lastName }}</title>
    <meta charset="utf-8">
</head>
<body>
<style>
    table td {
        font-family: sans-serif;
        text-align: center;
        padding: 3px;
    }
    tr td:first-of-type {
        text-align: left;
    }
    thead td, tfoot td {
        font-weight: bold;
        background-color: #ddd;
    }
    thead td {
        cursor: pointer;
    }
    tbody tr:nth-child(even) td {
        background-color: #eee;
    }
</style>
<script src="/sorttable.js"></script>

<h1>{{ $person->firstName }} {{ $person->lastName }}</h1>

<h2>Statistics</h2>
<h3>Hitting</h3>
<table class="sortable">
    <x-hitting-stat-header />
@foreach ($teams as $team)
    <tr>
        <td style="text-align:left;">
            <a href="{{ route('person.games', ['person' => $person->id, 'team' => $team->id]) }}">
                {{ $team->name }} - {{ $team->season }}
            </a>
        </td>
        <td>{{ $stats[$team->id]->G }}</td>
        <td>{{ $stats[$team->id]->PA }}</td>
        <td>{{ $stats[$team->id]->AB }}</td>
        <td>{{ $stats[$team->id]->R }}</td>
        <td>{{ $stats[$team->id]->H }}</td>
        <td>{{ $stats[$team->id]->stat('1') }}</td>
        <td>{{ $stats[$team->id]->stat('2') }}</td>
        <td>{{ $stats[$team->id]->stat('3') }}</td>
        <td>{{ $stats[$team->id]->stat('4') }}</td>
        <td>{{ $stats[$team->id]->RBI }}</td>
        <td>{{ $stats[$team->id]->SO }}</td>
        <td>{{ $stats[$team->id]->BBs }}</td>
        <td>{{ $stats[$team->id]->HPB }}</td>
        <td>{{ $stats[$team->id]->SB }}</td>
        <td>{{ $stats[$team->id]->CS }}</td>
        <td>{{ number_format($stats[$team->id]->AVG, 3) }}</td>
        <td>{{ number_format($stats[$team->id]->OBP, 3) }}</td>
        <td>{{ number_format($stats[$team->id]->SLG, 3) }}</td>
        <td>{{ number_format($stats[$team->id]->OPS, 3) }}</td>
        <td>{{ number_format($stats[$team->id]->ISO, 3) }}</td>
        <td>{{ number_format($stats[$team->id]->PPA, 2)}}</td>
    </tr>
@endforeach
    <tfoot>
        <x-hitting-stat-line header="Totals" :stats="$totals" />
    </tfoot>
</table>

<h3>Fielding</h3>
<table class="sortable">
    <x-fielding-stat-header />
@foreach ($teams as $team)
    <tr>
        <td style="text-align:left;">
            <a href="{{ route('person.games', ['person' => $person->id, 'team' => $team->id]) }}">
                {{ $team->name }} - {{ $team->season }}
            </a>
        </td>
        <td>{{ $stats[$team->id]->G }}</td>
        <td>{{ App\Helpers\StatsHelper::innings_format($stats[$team->id]->FI) }}</td>
        <td>{{ $stats[$team->id]->TC }}</td>
        <td>{{ $stats[$team->id]->PO }}</td>
        <td>{{ $stats[$team->id]->A }}</td>
        <td>{{ $stats[$team->id]->E }}</td>
        <td>{{ number_format($stats[$team->id]->FPCT, 3) }}</td>
        <td>{{ $stats[$team->id]->PB }}</td>
    </tr>
@endforeach
    <tfoot>
        <tr>
            <td>Totals</td>
            <td>{{ $totals->G }}</td>
            <td>{{ App\Helpers\StatsHelper::innings_format($totals->FI) }}</td>
            <td>{{ $totals->TC }}</td>
            <td>{{ $totals->PO }}</td>
            <td>{{ $totals->A }}</td>
            <td>{{ $totals->E }}</td>
            <td>{{ number_format($totals->FPCT, 3) }}</td>
            <td>{{ $totals->PB }}</td>
        </tr>
    </tfoot>
</table>


<h3>Pitching</h3>
<table class="sortable">
    <x-pitching-stat-header />
@foreach ($teams as $team)
    @if ($stats[$team->id]->IP)
    <tr>
        <td style="text-align:left;">
            <a href="{{ route('person.games', ['person' => $person->id, 'team' => $team->id]) }}">
                {{ $team->name }} - {{ $team->season }}
            </a>
        </td>
        <td>{{ $stats[$team->id]->GP }}</td>
        <td>{{ App\Helpers\StatsHelper::innings_format($stats[$team->id]->IP) }}</td>
        <td>{{ $stats[$team->id]->HA }}</td>
        <td>{{ $stats[$team->id]->K }}</td>
        <td>{{ $stats[$team->id]->BB }}</td>
        <td>{{ $stats[$team->id]->HBP }}</td>
        <td>{{ $stats[$team->id]->ER }}</td>
        <td>{{ $stats[$team->id]->RA }}</td>
        <td>{{ $stats[$team->id]->WP }}</td>
        <td>{{ $stats[$team->id]->POs }}</td>
        <td>{{ $stats[$team->id]->BFP }}</td>
        <td>{{ $stats[$team->id]->Balls }}</td>
        <td>{{ $stats[$team->id]->Strikes }}</td>
        <td>{{ $stats[$team->id]->Pitches }}</td>
        <td>{{ number_format($stats[$team->id]->ERA, 2) }}</td>
        <td>{{ number_format($stats[$team->id]->StrkPct * 100, 1) }}%</td>
        <td>{{ number_format($stats[$team->id]->KP9, 1) }}</td>
        <td>{{ number_format($stats[$team->id]->BBP9, 1) }}</td>
        <td>{{ number_format($stats[$team->id]->KPBB, 1) }}</td>
    </tr>
    @endif
@endforeach
    <tfoot>
        <tr>
            <td>Totals</td>
            <td>{{ $totals->GP }}</td>
            <td>{{ App\Helpers\StatsHelper::innings_format($totals->IP) }}</td>
            <td>{{ $totals->HA }}</td>
            <td>{{ $totals->K }}</td>
            <td>{{ $totals->BB }}</td>
            <td>{{ $totals->HBP }}</td>
            <td>{{ $totals->ER }}</td>
            <td>{{ $totals->RA }}</td>
            <td>{{ $totals->WP }}</td>
            <td>{{ $totals->POs }}</td>
            <td>{{ $totals->BFP }}</td>
            <td>{{ $totals->Balls }}</td>
            <td>{{ $totals->Strikes }}</td>
            <td>{{ $totals->Pitches }}</td>
            <td>{{ number_format($totals->ERA, 2) }}</td>
            <td>{{ number_format($totals->StrkPct * 100, 1) }}%</td>
            <td>{{ number_format($totals->KP9, 1) }}</td>
            <td>{{ number_format($totals->BBP9, 1) }}</td>
            <td>{{ number_format($totals->KPBB, 1) }}</td>
        </tr>
    </tfoot>
</table>
</body>