<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $game->away_team->name }} @ {{ $game->home_team->name }} - Touch Score</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
<div id="app" data-game-id="{{ $game->id }}" data-game="{{ json_encode($game) }}" data-game-state="{{ $state }}" data-last-play="{{ $lastPlay }}"></div>

@vite(['resources/js/touch-score.js'])
</body>
</html>