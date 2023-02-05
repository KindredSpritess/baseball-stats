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
    <thead>
        <tr>
            <td style="text-align:left;">Name</td>
            <td>G</td>
            <td>PA</td>
            <td>AB</td>
            <td>R</td>
            <td>H</td>
            <td>1B</td>
            <td>2B</td>
            <td>3B</td>
            <td>HR</td>
            <td>RBI</td>
            <td>SO</td>
            <td>BB</td>
            <td>HBP</td>
            <td>SB</td>
            <td>CS</td>
            <td>AVG</td>
            <td>OBP</td>
            <td>SLG</td>
            <td>OPS</td>
            <td>ISO</td>
        </tr>
    </thead>
@foreach ($people as $person)
    <tr>
        <td style="text-align:left;"><span style="text-transform:uppercase;font-weight:520">{{ $person->lastName }}</span>,&nbsp;{{ $person->firstName }}</td>
        <td>{{ $stats[$person->id]->G }}</td>
        <td>{{ $stats[$person->id]->PA }}</td>
        <td>{{ $stats[$person->id]->AB }}</td>
        <td>{{ $stats[$person->id]->R }}</td>
        <td>{{ $stats[$person->id]->H }}</td>
        <td>{{ $stats[$person->id]->stat('1') }}</td>
        <td>{{ $stats[$person->id]->stat('2') }}</td>
        <td>{{ $stats[$person->id]->stat('3') }}</td>
        <td>{{ $stats[$person->id]->stat('4') }}</td>
        <td>{{ $stats[$person->id]->RBI }}</td>
        <td>{{ $stats[$person->id]->SO }}</td>
        <td>{{ $stats[$person->id]->BBs }}</td>
        <td>{{ $stats[$person->id]->HPB }}</td>
        <td>{{ $stats[$person->id]->SB }}</td>
        <td>{{ $stats[$person->id]->CS }}</td>
        <td>{{ number_format($stats[$person->id]->AVG, 3) }}</td>
        <td>{{ number_format($stats[$person->id]->OBP, 3) }}</td>
        <td>{{ number_format($stats[$person->id]->SLG, 3) }}</td>
        <td>{{ number_format($stats[$person->id]->OPS, 3) }}</td>
        <td>{{ number_format($stats[$person->id]->ISO, 3) }}</td>
    </tr>
@endforeach
    <tfoot>
        <tr>
            <td>Totals</td>
            <td>{{ $totals->G }}</td>
            <td>{{ $totals->PA }}</td>
            <td>{{ $totals->AB }}</td>
            <td>{{ $totals->R }}</td>
            <td>{{ $totals->H }}</td>
            <td>{{ $totals->stat('1') }}</td>
            <td>{{ $totals->stat('2') }}</td>
            <td>{{ $totals->stat('3') }}</td>
            <td>{{ $totals->stat('4') }}</td>
            <td>{{ $totals->RBI }}</td>
            <td>{{ $totals->SO }}</td>
            <td>{{ $totals->BBs }}</td>
            <td>{{ $totals->HPB }}</td>
            <td>{{ $totals->SB }}</td>
            <td>{{ $totals->CS }}</td>
            <td>{{ number_format($totals->AVG, 3) }}</td>
            <td>{{ number_format($totals->OBP, 3) }}</td>
            <td>{{ number_format($totals->SLG, 3) }}</td>
            <td>{{ number_format($totals->OPS, 3) }}</td>
            <td>{{ number_format($totals->ISO, 3) }}</td>
        </tr>
    </tfoot>
</table>

<h3>Fielding</h3>
<table class="sortable">
    <thead>
        <tr>
            <td style="text-align:left;">Name</td>
            <td>G</td>
            <td>INN</td>
            <td>TC</td>
            <td>PO</td>
            <td>A</td>
            <td>E</td>
            <td>FPCT</td>
            <td>PB</td>
        </tr>
    </thead>
@foreach ($people as $person)
    <tr>
        <td style="text-align:left;"><span style="text-transform:uppercase;font-weight:520">{{ $person->lastName }}</span>,&nbsp;{{ $person->firstName }}</td>
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
    <thead>
        <tr>
            <td style="text-align:left;">Name</td>
            <td>G</td>
            <td>INN</td>
            <td>H</td>
            <td>K</td>
            <td>BB</td>
            <td>HBP</td>
            <td>ER</td>
            <td>RA</td>
            <td>WP</td>
            <td>PO</td>
            <td>BFP</td>
            <td>Balls</td>
            <td>Str</td>
            <td>Pit</td>
            <td>ERA</td>
            <td>Strk %</td>
            <td>K/9</td>
            <td>BB/9</td>
            <td>K/BB</td>
        </tr>
    </thead>
@foreach ($people as $person)
    @if ($stats[$person->id]->IP)
    <tr>
        <td style="text-align:left;"><span style="text-transform:uppercase;font-weight:520">{{ $person->lastName }}</span>,&nbsp;{{ $person->firstName }}</td>
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