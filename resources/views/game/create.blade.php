<h1>Create New Game</h1>
<form action="/game/store" method="POST">
    @csrf
    <div>
        <label for="location">Venue:</label>
        <input id="location" name="location" />
    </div>
    <div>
        <label for="firstPitch">Game Time:</label>
        <input type="datetime-local" name="firstPitch" />
    </div>
    <div>
        <label for="away">Away Team:</label>
        <select id="away" name="away">
            @foreach ($teams as $team)
                <option value="{{ $team->id }}">{{ $team->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="home">Home Team:</label>
        <select id="home" name="home">
            @foreach ($teams as $team)
                <option value="{{ $team->id }}">{{ $team->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <button>Create Game</button>
    </div>
</form>