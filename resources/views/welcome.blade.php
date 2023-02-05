@foreach ($seasons as $season)
<h2>{{ $season }}</h2>
<h3>Games</h3>
<ul>
@foreach ($games as $game)
    <li><a href="{{ route('game', ['game' => $game->id]) }}">{{ $game->away_team->name }} @ {{ $game->home_team->name }}</a></li>
@endforeach
</ul>
<h3>Teams</h3>
<ul>
@foreach ($teams as $team)
    <li><a href="{{ route('team', ['team' => $team->id]) }}">{{ $team->name }}</a></li>
@endforeach
</ul>
@endforeach