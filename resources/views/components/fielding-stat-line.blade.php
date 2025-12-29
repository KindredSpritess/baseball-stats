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
    <td>
      @if($hidePosition ?? false)
      @elseif ($stats->Position)
        {{ App\Helpers\StatsHelper::position($stats->Position) }}
      @else
        {{ App\Helpers\StatsHelper::positions($stats->Positions) }}
      @endif
    </td>
    @unless ($singleGameStats ?? false)<td>{{ $stats->G }}</td>@endunless
    <td>{{ App\Helpers\StatsHelper::innings_format($stats->FI) }}</td>
    @unless ($singleGameStats ?? false)<td>{{ $stats->TC }}</td>@endunless
    <td>{{ $stats->PO }}</td>
    <td>{{ $stats->A }}</td>
    <td>{{ $stats->E }}</td>
    @unless ($singleGameStats ?? false)<td>{{ number_format($stats->FPCT, 3) }}</td>@endunless
    @unless ($singleGameStats ?? false)<td>{{ number_format($stats->RF, 2) }}</td>@endunless
    <td>{{ $stats->PB }}</td>
    <td>{{ $stats->CCS }}</td>
    <td>{{ $stats->CSB }}</td>
</tr>