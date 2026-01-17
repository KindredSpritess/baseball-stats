<tr>
    <td style="text-align:left;"
        @isset($sort)
        sorttable_customkey="{{ $sort }}"
        @endisset
    >
      @if (isset($link))
        <a href="{{ $link }}">{{ $header }}</a>
      @else
        {{ $header }}
      @endif
    </td>
    @unless ($singleGameStats ?? false)<td>{{ $stats->G }}</td>@endunless
    <td>{{ $stats->PA }}</td>
    <td>{{ $stats->AB }}</td>
    <td>{{ $stats->R }}</td>
    <td>{{ $stats->H }}</td>
    <td>{{ $stats->stat('1') }}</td>
    <td>{{ $stats->stat('2') }}</td>
    <td>{{ $stats->stat('3') }}</td>
    <td>{{ $stats->stat('4') }}</td>
    <td>{{ $stats->RBI }}</td>
    <td>{{ $stats->SO }}</td>
    @if ($singleGameStats ?? false)<td>{{ $stats->SAB }}</td>@endif
    @if ($singleGameStats ?? false)<td>{{ $stats->SAF }}</td>@endif
    <td>{{ $stats->BBs }}</td>
    <td>{{ $stats->HPB }}</td>
    @if ($singleGameStats ?? false)<td>{{ $stats->CI }}</td>@endif
    <td>{{ $stats->SB }}</td>
    <td>{{ $stats->CS }}</td>
    <td>{{ $stats->GDP }}</td>
    @unless ($singleGameStats ?? false)<td>{{ number_format($stats->AVG, 3) }}</td>@endunless
    @unless ($singleGameStats ?? false)<td>{{ number_format($stats->OBP, 3) }}</td>@endunless
    @unless ($singleGameStats ?? false)<td>{{ number_format($stats->SLG, 3) }}</td>@endunless
    @unless ($singleGameStats ?? false)<td>{{ number_format($stats->OPS, 3) }}</td>@endunless
    @unless ($singleGameStats ?? false)<td>{{ number_format($stats->BABIP, 3) }}</td>@endunless
    @unless ($singleGameStats ?? false)<td>{{ number_format($stats->ISO, 3) }}</td>@endunless
    @unless ($singleGameStats ?? false)<td>{{ number_format($stats->PPA, 2)}}</td>@endunless
</tr>