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
    <td>{{ $stats->G }}</td>
    <td>{{ App\Helpers\StatsHelper::innings_format($stats->FI) }}</td>
    <td>{{ $stats->TC }}</td>
    <td>{{ $stats->PO }}</td>
    <td>{{ $stats->A }}</td>
    <td>{{ $stats->E }}</td>
    <td>{{ number_format($stats->FPCT, 3) }}</td>
    <td>{{ number_format($stats->RF, 2) }}</td>
    <td>{{ $stats->PB }}</td>
    <td>{{ $stats->CCS }}</td>
    <td>{{ $stats->CSB }}</td>
</tr>