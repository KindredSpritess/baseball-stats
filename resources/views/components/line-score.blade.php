<table class="line-score">
    <tr class="header">
        <th>&nbsp;</th>
        <th>R</th>
        <th>H</th>
        <th>E</th>
    </tr>
    <tr class="away">
        <th>{{ $game->away_team->short_name }}</th>
        <td>@{{ stats.away?.R }}</td>
        <td>@{{ stats.away?.H }}</td>
        <td>@{{ stats.away?.E }}</td>
    </tr>
    <tr class="home">
        <th>{{ $game->home_team->short_name }}</th>
        <td>@{{ stats.home?.R }}</td>
        <td>@{{ stats.home?.H }}</td>
        <td>@{{ stats.home?.E }}</td>
    </tr>
</table>