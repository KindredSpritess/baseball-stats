<thead>
  <tr>
    <td style="text-align:left;">Name</td>
    @unless ($singleGameStats ?? false)<td>G</td>@endunless
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
    <td>GDP</td>
    @unless ($singleGameStats ?? false)<td>AVG</td>@endunless
    @unless ($singleGameStats ?? false)<td>OBP</td>@endunless
    @unless ($singleGameStats ?? false)<td>SLG</td>@endunless
    @unless ($singleGameStats ?? false)<td>OPS</td>@endunless
    @unless ($singleGameStats ?? false)<td>ISO</td>@endunless
    @unless ($singleGameStats ?? false)<td>P/PA</td>@endunless
  </tr>
</thead>