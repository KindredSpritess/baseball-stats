<div style="padding:10px;" class="{{ $team->id === $game->away_team->id ? 'away-team-colors box-score-away' : 'home-team-colors box-score-home' }}">
    <h3 id="{{ $team->short_name }}" style="color: var(--team-primary); border-bottom: 2px solid var(--team-secondary); padding-bottom: 5px;">{{ $team->name }}</h3>
    <table style="text-align:center">
        <tr>
            <th class="scorers" style="background-color: var(--team-primary); color: white;">#</th>
            <th style="text-align:left; background-color: var(--team-primary); color: white;">Name</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">PA</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">AB</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">R</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">H</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">RBI</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">SO</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">BB</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">&nbsp;</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">PO</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">A</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">E</th>
        </tr>
        @foreach ($lineup as $i => $spot)
        @foreach ($spot as $player)
        <tr class="{{ $i == $atbat ? 'atbat' : '' }}">
            <td class="scorers">{{ $loop->index === 0 ? ($i+1) : '' }}</td>
            <td style="text-align:left;">
                @spaceless
                <a href="{{ route('person.show', ['person' => $player->person->id]) }}">
                    <span style="text-transform:uppercase;font-weight:520">{{ $player->person->lastName }}</span>,&nbsp;{{ $player->person->firstName }}
                </a>
                @if ($player->number)
                    <sup>#{{ $player->number }}</sup>
                @endif
                @if ($defending)
                <i class="fa-solid fa-up-down-left-right scorers" onclick="dsub({{ $i + 1 }})"></i>
                @endif
                @endspaceless
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
        @endforeach
        <tr style="font-weight: bold;">
            <td class="scorers" colspan="2" style="text-align:left;">Total</td>
            <td class="viewers" style="text-align:left;">Total</td>
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
    <h4 style="color: var(--team-primary); border-bottom: 1px solid var(--team-secondary);">Pitching</h4>
    <table style="text-align:center">
        <tr>
            <th class="scorers" style="background-color: var(--team-primary); color: white;">#</th>
            <th style="text-align:left; background-color: var(--team-primary); color: white;">Name</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">INN</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">ER</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">R</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">H</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">K</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">BB</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">TBF</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">B</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">S</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">Pit</th>
        </tr>
        @foreach ($lineup as $spot)
        @foreach ($spot as $player)
        @if ($player->stats['Balls'] ?? $player->stats['Strikes'] ?? 0)
        <tr>
            <td class="scorers">{{ $player->number }}</td>
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
        @endforeach
        <tr style="font-weight: bold;">
            <td class="scorers" colspan="2" style="text-align:left;">Total</td>
            <td class="viewers" style="text-align:left;">Total</td>
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