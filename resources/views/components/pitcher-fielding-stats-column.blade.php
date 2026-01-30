<td rowspan="8" class="{{ $class }}" style="vertical-align:top">
  <table class="pitcher-stats-subtable">
  @foreach ($groupedBatters['P'] as $pitcher)
    @php
    $stats = (new \App\Helpers\StatsHelper($pitcher['player']->stats ?? []))->derive();
    @endphp
    <tr style="height: calc(88px / {{ isset($groupedBatters['P']) ? max(6, count($groupedBatters['P'])) : 1 }})">
      <td>
        @if (isset($stat))
          {{ $stats->$stat }}
        @elseif (isset($detail))
          {{ $pitcher[$detail] }}
        @elseif (isset($position))
          @foreach ($pitcher['positions'] as [$inning, $out, $pos, $half])
            @if ($loop->first)
              {{ $pos }}<br/>
            @else
              <span style="text-decoration:line-through">{{ $pos }}</span><br/>
            @endif
          @endforeach
        @elseif (isset($changes))
          @foreach ($pitcher['positions'] as [$inning, $out, $pos, $half])
            @if ($inning !== 1 || $out !== 0)
              <span @style(["text-decoration:overline" => !$half, 'text-decoration:underline' => $half])>{{ $inning }}@if($out).{{ $out }}@endif</span><br/>
            @else
              &nbsp;<br/>
            @endif
          @endforeach
        @endif
      </td> 
    </tr>
  @endforeach
  </table>
</td>