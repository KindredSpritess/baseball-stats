<head>
    <title>{{ $team->name }}</title>
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

<h1>{{ $team->name }} - {{ $team->season }}</h1>

<h2>Statistics</h2>
<h3>Hitting</h3>
<table class="sortable">
    <x-hitting-stat-header />
@foreach ($people as $person)
    <x-hitting-stat-line header="{{ $person->lastName }}, {{ $person->firstName }}" :stats="$stats[$person->id]" :link="route('person.show', ['person' => $person->id])" />
@endforeach
    <tfoot>
        <x-hitting-stat-line header="Totals" :stats="$totals" />
    </tfoot>
</table>

<h3>Fielding</h3>
<table class="sortable">
    <x-fielding-stat-header />
@foreach ($people as $person)
    <tr>
        <td style="text-align:left;">
            <a href="{{ route('person.show', ['person' => $person->id]) }}">
                <span style="text-transform:uppercase;font-weight:520">{{ $person->lastName }}</span>,&nbsp;{{ $person->firstName }}
            </a>
        </td>
        <td>{{ $stats[$person->id]->G }}</td>
        <td>{{ App\Helpers\StatsHelper::innings_format($stats[$person->id]->FI) }}</td>
        <td>{{ $stats[$person->id]->TC }}</td>
        <td>{{ $stats[$person->id]->PO }}</td>
        <td>{{ $stats[$person->id]->A }}</td>
        <td>{{ $stats[$person->id]->E }}</td>
        <td>{{ number_format($stats[$person->id]->FPCT, 3) }}</td>
        <td>{{ $stats[$person->id]->PB }}</td>
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
@foreach ($people as $person)
    @if ($stats[$person->id]->IP)
    <tr>
        <td style="text-align:left;">
            <a href="{{ route('person.show', ['person' => $person->id]) }}">
                <span style="text-transform:uppercase;font-weight:520">{{ $person->lastName }}</span>,&nbsp;{{ $person->firstName }}
            </a>
        </td>
        <td>{{ $stats[$person->id]->GP }}</td>
        <td>{{ App\Helpers\StatsHelper::innings_format($stats[$person->id]->IP) }}</td>
        <td>{{ $stats[$person->id]->HA }}</td>
        <td>{{ $stats[$person->id]->K }}</td>
        <td>{{ $stats[$person->id]->BB }}</td>
        <td>{{ $stats[$person->id]->HBP }}</td>
        <td>{{ $stats[$person->id]->ER }}</td>
        <td>{{ $stats[$person->id]->RA }}</td>
        <td>{{ $stats[$person->id]->WP }}</td>
        <td>{{ $stats[$person->id]->POs }}</td>
        <td>{{ $stats[$person->id]->BFP }}</td>
        <td>{{ $stats[$person->id]->Balls }}</td>
        <td>{{ $stats[$person->id]->Strikes }}</td>
        <td>{{ $stats[$person->id]->Pitches }}</td>
        <td>{{ number_format($stats[$person->id]->ERA, 2) }}</td>
        <td>{{ number_format($stats[$person->id]->StrkPct * 100, 1) }}%</td>
        <td>{{ number_format($stats[$person->id]->KP9, 1) }}</td>
        <td>{{ number_format($stats[$person->id]->BBP9, 1) }}</td>
        <td>{{ number_format($stats[$person->id]->KPBB, 1) }}</td>
        <td>{{ number_format($stats[$person->id]->FPSPCT, 2) }}</td>
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
            <td>{{ number_format($totals->FPSPCT, 2) }}</td>
        </tr>
    </tfoot>
</table>
</body>