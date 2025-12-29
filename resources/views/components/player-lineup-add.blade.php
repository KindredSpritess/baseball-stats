<div class="player-lineup-add">
    <div class="player-lineup-header">
        <h4>Add Player to Lineup</h4>
        <button type="button" class="close-btn" aria-label="Close">&times;</button>
    </div>
    <div class="form-and-players-container">
        <div class="form-column">
            <form class="add-player-form" action="{{ route('gamelog', ['game' => $game->id]) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" class="team-short-name" value="{{ $team->short_name }}">
                <input type="hidden" class="team-id" value="{{ $team->id }}">
                <input type="hidden" class="sub-prefix" />
                <div class="form-group">
                    <label for="player-search">Player Name:</label>
                    <input 
                        type="text" 
                        class="form-control player-search" 
                        placeholder="Start typing a player name..." 
                        autocomplete="off"
                    >
                    <div class="suggestions-container"></div>
                </div>
                <div class="form-group">
                    <label for="player-number">Number:</label>
                    <input type="text" class="form-control player-number" placeholder="#" maxlength="2">
                </div>
                <div class="form-group">
                    <label for="player-position">Position:</label>
                    <div class="position-buttons">
                        <button type="button" class="position-btn" data-position="1">1 - P</button>
                        <button type="button" class="position-btn" data-position="2">2 - C</button>
                        <button type="button" class="position-btn" data-position="3">3 - 1B</button>
                        <button type="button" class="position-btn" data-position="4">4 - 2B</button>
                        <button type="button" class="position-btn" data-position="5">5 - 3B</button>
                        <button type="button" class="position-btn" data-position="6">6 - SS</button>
                        <button type="button" class="position-btn" data-position="7">7 - LF</button>
                        <button type="button" class="position-btn" data-position="8">8 - CF</button>
                        <button type="button" class="position-btn" data-position="9">9 - RF</button>
                        <button type="button" class="position-btn" data-position="DH">DH</button>
                        <button type="button" class="position-btn" data-position="EH">EH</button>
                        <button type="button" class="position-btn" data-position="PH">PH</button>
                        <button type="button" class="position-btn" data-position="PR">PR</button>
                    </div>
                    <input type="hidden" class="player-position-input">
                    <div class="selected-position">Selected: <span>None</span></div>
                </div>
                <button type="submit" class="btn btn-primary">Add to Lineup</button>
            </form>
        </div>
        <div class="previous-players-column">
            <div class="previous-players-container">
                <label>Previous Players:</label>
                <div class="previous-players-list"></div>
            </div>
        </div>
    </div>
</div>
