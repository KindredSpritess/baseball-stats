<table class="line-score">
    <tr class="header">
        <th>&nbsp;</th>
        <th>R</th>
        <th>H</th>
        <th>E</th>
    </tr>
    <tr class="away">
        <th>{{ $game->away_team->short_name }}</th>
        <td>{{ $away->R }}</td>
        <td>{{ $away->H }}</td>
        <td>{{ $away->E }}</td>
    </tr>
    <tr class="home">
        <th>{{ $game->home_team->short_name }}</th>
        <td>{{ $home->R }}</td>
        <td>{{ $home->H }}</td>
        <td>{{ $home->E }}</td>
    </tr>
</table>