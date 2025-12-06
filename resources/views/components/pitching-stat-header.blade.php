<thead>
  <tr>
    <td style="text-align:left;">Name</td>
    @unless($singleGameStats ?? false)<td>G</td>@endunless
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
    @unless($singleGameStats ?? false)<td>ERA</td>@endunless
    @unless($singleGameStats ?? false)<td>WHIP</td>@endunless
    @unless($singleGameStats ?? false)<td>Strk %</td>@endunless
    @unless($singleGameStats ?? false)<td>K/9</td>@endunless
    @unless($singleGameStats ?? false)<td>BB/9</td>@endunless
    @unless($singleGameStats ?? false)<td>K/BB</td>@endunless
    @unless($singleGameStats ?? false)<td>FPS %</td>@endunless
    @unless($singleGameStats ?? false)<td>P/PA</td>@endunless
    @if($singleGameStats ?? false)<td>FPS</td>@endunless
    <td>IR(/S)</td>
  </tr>
</thead>