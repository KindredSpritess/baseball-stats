<tr>
    <td style="text-align:left;"
        @isset($sort)
        sorttable_customkey="{{ $sort }}"
        @endisset
    >
        @isset($link)
            <a href="{{ $link }}">{{ $header }}</a>
        @else
            {{ $header }}
        @endisset
    </td>
    @unless ($singleGameStats ?? false)<td>{{ $stats->GP }}</td>@endunless
    <td>{{ App\Helpers\StatsHelper::innings_format($stats->IP) }}</td>
    <td>{{ $stats->HA }}</td>
    <td>{{ $stats->K }}</td>
    <td>{{ $stats->BB }}</td>
    <td>{{ $stats->HBP }}</td>
    <td>{{ $stats->ER }}</td>
    <td>{{ $stats->RA }}</td>
    <td>{{ $stats->WP }}</td>
    <td>{{ $stats->POs }}</td>
    <td>{{ $stats->BFP }}</td>
    <td>{{ $stats->Balls }}</td>
    <td>{{ $stats->Strikes }}</td>
    <td>{{ $stats->Pitches }}</td>
    @unless ($singleGameStats ?? false)<td>{{ number_format($stats->ERA, 2) }}</td>@endunless
    @unless ($singleGameStats ?? false)<td>{{ number_format($stats->WHIP, 3) }}</td>@endunless
    @unless ($singleGameStats ?? false)<td>{{ number_format($stats->StrkPct * 100, 1) }}%</td>@endunless
    @unless ($singleGameStats ?? false)<td>{{ number_format($stats->KP9, 1) }}</td>@endunless
    @unless ($singleGameStats ?? false)<td>{{ number_format($stats->BBP9, 1) }}</td>@endunless
    @unless ($singleGameStats ?? false)<td>{{ number_format($stats->KPBB, 1) }}</td>@endunless
    @unless ($singleGameStats ?? false)<td>{{ number_format($stats->FPSPCT, 2) }}</td>@endunless
    @unless ($singleGameStats ?? false)<td>{{ number_format($stats->PPBFP, 2) }}</td>@endunless
    @if ($singleGameStats ?? false)<td>{{ $stats->FPS }}</td>@endif
    <td>{{ $stats->stat('IR') }}/{{ $stats->stat('IRS') }}</td>
</tr>