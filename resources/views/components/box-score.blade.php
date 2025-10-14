<div style="padding:10px 0 0 0;" class="{{ $team->id === $game->away_team->id ? 'away-team-colors box-score-away' : 'home-team-colors box-score-home' }}">
    <h3 id="{{ $team->short_name }}" style="color: var(--team-primary); border-bottom: 2px solid var(--team-secondary); padding-bottom: 5px;">{{ $team->name }}</h3>
    <table style="text-align:center">
        <tr>
            <th class="scorers" style="background-color: var(--team-primary); color: white;">#</th>
            <th style="text-align:left; background-color: var(--team-primary); color: white;">Name</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);" class="mobile-hide">PA</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">AB</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">R</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">H</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">RBI</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">SO</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">BB</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);" class="mobile-hide">&nbsp;</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);" class="mobile-hide">PO</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);" class="mobile-hide">A</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);" class="mobile-hide">E</th>
        </tr>
        @foreach ($lineup as $i => $spot)
        @foreach ($spot as $player)
        @php
            $playerStats = $stats[$player->id];
        @endphp
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
            <td class="mobile-hide">{{ $player->stats['PA'] ?? 0 }}</td>
            <td>{{ $player->stats['AB'] ?? 0 }}</td>
            <td>{{ $player->stats['R'] ?? 0 }}</td>
            <td>{{ ($player->stats['1'] ?? 0) + ($player->stats['2'] ?? 0) + ($player->stats['3'] ?? 0) + ($player->stats['4'] ?? 0) }}</td>
            <td>{{ $player->stats['RBI'] ?? 0 }}</td>
            <td>{{ $player->stats['SO'] ?? $player->stats['Ks'] ?? 0 }}</td>
            <td>{{ $player->stats['BBs'] ?? 0 }}</td>
            <td class="mobile-hide">&nbsp;</td>
            <td class="mobile-hide">{{ $player->stats['PO'] ?? 0 }}</td>
            <td class="mobile-hide">{{ $player->stats['A'] ?? 0 }}</td>
            <td class="mobile-hide">{{ $player->stats['E'] ?? 0 }}</td>
        </tr>
        @endforeach
        @endforeach
        <tr style="font-weight: bold;">
            <td class="scorers" colspan="2" style="text-align:left;">Total</td>
            <td class="viewers" style="text-align:left;">Total</td>
            <td class="mobile-hide">{{ $totals->PA }}</td>
            <td>{{ $totals->AB }}</td>
            <td>{{ $totals->R }}</td>
            <td>{{ $totals->H }}</td>
            <td>{{ $totals->RBI }}</td>
            <td>{{ $totals->SO }}</td>
            <td>{{ $totals->BBs }}</td>
            <td class="mobile-hide">&nbsp;</td>
            <td class="mobile-hide">{{ $totals->PO }}</td>
            <td class="mobile-hide">{{ $totals->A }}</td>
            <td class="mobile-hide">{{ $totals->E }}</td>
        </tr>
    </table>

    @if ($totals->stats['2'] ?? 0)
    <div class="viewers extra-stats">
        <b>2B:</b>
        @foreach ($lineup as $i => $spot)
        @foreach ($spot as $player)
            @if ($player->stats['2'] ?? 0)
                {{ $player->person->lastName }} {{ $player->stats['2'] ?? 0 }}{{ $loop->last ? '.' : ';' }}
            @endif
        @endforeach
        @endforeach
    </div>
    @endif
    @if ($totals->stats['3'] ?? 0)
    <div class="viewers extra-stats">
        <b>3B:</b>
        @foreach ($lineup as $i => $spot)
        @foreach ($spot as $player)
            @if ($player->stats['3'] ?? 0)
                {{ $player->person->lastName }} {{ $player->stats['3'] ?? 0 }}{{ $loop->last ? '.' : ';' }}
            @endif
        @endforeach
        @endforeach
    </div>
    @endif
    @if ($totals->stats['4'] ?? 0)
    <div class="viewers extra-stats">
        <b>HR:</b>
        @foreach ($lineup as $i => $spot)
        @foreach ($spot as $player)
            @if ($player->stats['4'] ?? 0)
                {{ $player->person->lastName }} {{ $player->stats['4'] ?? 0 }}{{ $loop->last ? '.' : ';' }}
            @endif
        @endforeach
        @endforeach
    </div>
    @endif
    @if ($totals->TB ?? 0)
    <div class="viewers extra-stats">
        <b>Total Bases:</b>
        @foreach ($lineup as $i => $spot)
        @foreach ($spot as $player)
            @if ($stats[$player->id]->TB ?? 0)
                {{ $player->person->lastName }} {{ $stats[$player->id]->TB ?? 0 }}{{ $loop->last ? '.' : ';' }}
            @endif
        @endforeach
        @endforeach
    </div>
    @endif
    @if ($totals->SB)
    <div class="viewers extra-stats">
        <b>Stolen Bases:</b>
        @foreach ($lineup as $i => $spot)
        @foreach ($spot as $player)
            @if ($player->stats['SB'] ?? 0)
                {{ $player->person->lastName }} {{ $player->stats['SB'] ?? 0 }}{{ $loop->last ? '.' : ';' }}
            @endif
        @endforeach
        @endforeach
    </div>
    @endif
    @if ($totals->CS)
    <div class="viewers extra-stats">
        <b>Caught Stealing:</b>
        @foreach ($lineup as $i => $spot)
        @foreach ($spot as $player)
            @if ($player->stats['CS'] ?? 0)
                {{ $player->person->lastName }} {{ $player->stats['CS'] ?? 0 }}{{ $loop->last ? '.' : ';' }}
            @endif
        @endforeach
        @endforeach
    </div>
    @endif

    <!-- Fielding extra stats -->
    @if ($totals->PB)
    <div class="viewers extra-stats">
        <b>Passed Balls:</b>
        @foreach ($lineup as $i => $spot)
        @foreach ($spot as $player)
            @if ($player->stats['PB'] ?? 0)
                {{ $player->person->lastName }} {{ $player->stats['PB'] ?? 0 }}{{ $loop->last ? '.' : ';' }}
            @endif
        @endforeach
        @endforeach
    </div>
    @endif
    @if ($totals->E)
    <div class="viewers extra-stats">
        <b>Errors:</b>
        @foreach ($lineup as $i => $spot)
        @foreach ($spot as $player)
            @if ($player->stats['E'] ?? 0)
                {{ $player->person->lastName }} {{ $player->stats['E'] ?? 0 }}{{ $loop->last ? '.' : ';' }}
            @endif
        @endforeach
        @endforeach
    </div>
    @endif

    <!-- Pitching Stats -->
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
            <th style="background-color: var(--team-secondary); color: var(--team-primary);" class="mobile-hide">TBF</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);" class="mobile-hide">B</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);" class="mobile-hide">S</th>
            <th style="background-color: var(--team-secondary); color: var(--team-primary);">Pit</th>
        </tr>
        @foreach ($pitchers as $player)
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
            <td class="mobile-hide">{{ $player->stats['BFP'] ?? 0 }}</td>
            <td class="mobile-hide">{{ $player->stats['Balls'] ?? 0 }}</td>
            <td class="mobile-hide">{{ $player->stats['Strikes'] ?? 0 }}</td>
            <td>{{ ($player->stats['Balls'] ?? 0) + ($player->stats['Strikes'] ?? 0) }}</td>
        </tr>
        @endif
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
            <td class="mobile-hide">{{ $totals->BFP }}</td>
            <td class="mobile-hide">{{ $totals->Balls }}</td>
            <td class="mobile-hide">{{ $totals->Strikes }}</td>
            <td>{{ $totals->Pitches }}</td>
        </tr>
    </table>
    @if ($totals->HPB)
    <div class="viewers extra-stats">
        <b>HBP:</b>
        @foreach ($pitchers as $i => $player)
            @if ($player->stats['HPB'] ?? 0)
                {{ $player->person->lastName }} {{ $player->stats['HPB'] ?? 0 }}{{ $loop->last ? '.' : ';' }}
            @endif
        @endforeach
    </div>
    @endif
    @if ($totals->WP)
    <div class="viewers extra-stats">
        <b>Wild Pitches:</b>
        @foreach ($pitchers as $i => $player)
            @if ($player->stats['WP'] ?? 0)
                {{ $player->person->lastName }} {{ $player->stats['WP'] ?? 0 }}{{ $loop->last ? '.' : ';' }}
            @endif
        @endforeach
    </div>
    @endif
    <div class="viewers extra-stats">
        <b>Strikes-balls:</b>
        @foreach ($pitchers as $player)
            {{ $player->person->lastName }} {{ $player->stats['Strikes'] ?? 0 }}-{{ $player->stats['Balls'] ?? 0 }}{{ $loop->last ? '.' : ';' }}
        @endforeach
    </div>
    <div class="viewers extra-stats">
        <b>Batters faced:</b>
        @foreach ($pitchers as $player)
            {{ $player->person->lastName }} {{ $player->stats['BFP'] ?? 0 }}{{ $loop->last ? '.' : ';' }}
        @endforeach
    </div>
</div>