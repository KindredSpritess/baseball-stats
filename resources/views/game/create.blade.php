@extends('layouts.main')
@section('title')
Create New Game
@endsection

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">Create New Game</h1>
    </div>

    <div class="form-container">
        <form action="/game/store" method="POST" class="game-create-form">
            @csrf
            <div class="form-group">
                <label for="location" class="form-label">Venue:</label>
                <input id="location" name="location" class="form-input" required />
            </div>
            <div class="form-group">
                <label for="firstPitch" class="form-label">Game Time:</label>
                <input type="datetime-local" name="firstPitch" class="form-input" required />
                <input type="hidden" name="timezone" value="" />
            </div>
            <div class="form-group">
                <label for="away" class="form-label">Away Team:</label>
                <select id="away" name="away" class="form-select" required>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="home" class="form-label">Home Team:</label>
                <select id="home" name="home" class="form-select" required>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" name="action" value="score" class="btn btn-primary">Create and Score</button>
                <button type="submit" name="action" value="create" class="btn btn-secondary">Create</button>
            </div>
        </form>
    </div>
</div>

<script>
    const timezoneInput = document.querySelector('input[name="timezone"]');
    timezoneInput.value = Intl.DateTimeFormat().resolvedOptions().timeZone;
</script>
@endsection