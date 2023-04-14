@foreach ($seasons as $season)
<h2>{{ $season }}</h2>
<h3>Games</h3>
<ul>
@foreach ($games as $game)
    @if ($game->home_team->season === $season)
        <li><a href="{{ route('game', ['game' => $game->id]) }}">{{ $game->away_team->name }} @ {{ $game->home_team->name }}</a></li>
    @endif
@endforeach
</ul>
<h3>Teams</h3>
<ul>
@foreach ($teams as $team)
    @if ($team->season === $season)
        <li><a href="{{ route('team', ['team' => $team->id]) }}">{{ $team->name }}</a></li>
    @endif
@endforeach
</ul>
@endforeach