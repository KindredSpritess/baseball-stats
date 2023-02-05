<h1>Create New Game</h1>
<form action="/game/store" method="POST">
    @csrf
    <div>
        <label for="location">Venue:</label>
        <input id="location" name="location" />
    </div>
    <div>
        <label for="away.name">Away Team:</label>
        <input id="away.name" name="away.name" />
    </div>
    <div>
        <label for="home.name">Home Team:</label>
        <input id="home.name" name="home.name" />
    </div>
    <div>
        <button>Create Game</button>
    </div>
</form>