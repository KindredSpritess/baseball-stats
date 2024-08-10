<div style="padding:10px;">
    <h3 id="{{ $team->short_name }}">{{ $team->name }}</h3>
    <table style="text-align:center">
        <tr>
            <th>#</th>
            <th style="text-align:left;">Name</th>
            <th>PA</th>
            <th>AB</th>
            <th>R</th>
            <th>H</th>
            <th>RBI</th>
            <th>SO</th>
            <th>BB</th>
            <th>&nbsp;</th>
            <th>PO</th>
            <th>A</th>
            <th>E</th>
        </tr>
        @foreach ($lineup as $player)
        <tr class="{{ $loop->index == $atbat ? 'atbat' : '' }}">
            <td>{{ $loop->iteration }}</td>
            <td style="text-align:left;">
                <a href="{{ route('person.show', ['person' => $player->person->id]) }}">
                    <span style="text-transform:uppercase;font-weight:520">{{ $player->person->lastName }}</span>,&nbsp;{{ $player->person->firstName }}</a><sup>#{{ $player->number }}</sup>
            </td>
            <td>{{ $player->stats['PA'] ?? 0 }}</td>
            <td>{{ $player->stats['AB'] ?? 0 }}</td>
            <td>{{ $player->stats['R'] ?? 0 }}</td>
            <td>{{ ($player->stats['1'] ?? 0) + ($player->stats['2'] ?? 0) + ($player->stats['3'] ?? 0) + ($player->stats['4'] ?? 0) }}</td>
            <td>{{ $player->stats['RBI'] ?? 0 }}</td>
            <td>{{ $player->stats['SO'] ?? $player->stats['Ks'] ?? 0 }}</td>
            <td>{{ $player->stats['BBs'] ?? 0 }}</td>
            <td>&nbsp;</td>
            <td>{{ $player->stats['PO'] ?? 0 }}</td>
            <td>{{ $player->stats['A'] ?? 0 }}</td>
            <td>{{ $player->stats['E'] ?? 0 }}</td>
        </tr>
        @endforeach
        <tr style="font-weight: bold;">
            <td colspan="2" style="text-align:left;">Total</td>
            <td>{{ $totals->PA }}</td>
            <td>{{ $totals->AB }}</td>
            <td>{{ $totals->R }}</td>
            <td>{{ $totals->H }}</td>
            <td>{{ $totals->RBI }}</td>
            <td>{{ $totals->SO }}</td>
            <td>{{ $totals->BBs }}</td>
            <td>&nbsp;</td>
            <td>{{ $totals->PO }}</td>
            <td>{{ $totals->A }}</td>
            <td>{{ $totals->E }}</td>
        </tr>
    </table>
    <h4>Pitching</h4>
    <table style="text-align:center">
        <tr>
            <th>#</th>
            <th style="text-align:left;">Name</th>
            <th>INN</th>
            <th>ER</th>
            <th>R</th>
            <th>H</th>
            <th>K</th>
            <th>BB</th>
            <th>TBF</th>
            <th>B</th>
            <th>S</th>
            <th>Pit</th>
        </tr>
        @foreach ($lineup as $player)
        @if ($player->stats['Balls'] ?? $player->stats['Strikes'] ?? 0)
        <tr>
            <td>{{ $player->number }}</td>
            <td style="text-align:left;"><span style="text-transform:uppercase;font-weight:520">{{ $player->person->lastName }}</span>,&nbsp;{{ $player->person->firstName }}</td>
            <td>{{ App\Helpers\StatsHelper::innings_format(($player->stats['TO'] ?? 0) / 3) }}</td>
            <td>{{ $player->stats['ER'] ?? 0 }}</td>
            <td>{{ $player->stats['RA'] ?? 0 }}</td>
            <td>{{ $player->stats['HA'] ?? 0 }}</td>
            <td>{{ $player->stats['K'] ?? 0 }}</td>
            <td>{{ $player->stats['BB'] ?? 0 }}</td>
            <td>{{ $player->stats['BFP'] ?? 0 }}</td>
            <td>{{ $player->stats['Balls'] ?? 0 }}</td>
            <td>{{ $player->stats['Strikes'] ?? 0 }}</td>
            <td>{{ ($player->stats['Balls'] ?? 0) + ($player->stats['Strikes'] ?? 0) }}</td>
        </tr>
        @endif
        @endforeach
        <tr style="font-weight: bold;">
            <td colspan="2" style="text-align:left;">Total</td>
            <td>{{ App\Helpers\StatsHelper::innings_format($totals->IP) }}</td>
            <td>{{ $totals->ER }}</td>
            <td>{{ $totals->RA }}</td>
            <td>{{ $totals->HA }}</td>
            <td>{{ $totals->K }}</td>
            <td>{{ $totals->BB }}</td>
            <td>{{ $totals->BFP }}</td>
            <td>{{ $totals->Balls }}</td>
            <td>{{ $totals->Strikes }}</td>
            <td>{{ $totals->Pitches }}</td>
        </tr>
    </table>
</div>