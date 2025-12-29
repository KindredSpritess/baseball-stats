<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js"></script>
    <script src="/jquery.min.js"></script>
    <link rel="stylesheet" href="/styles.css" />
    <link rel="stylesheet" href="/game.css" />
    <script src="https://kit.fontawesome.com/cc3e56010d.js" crossorigin="anonymous"></script>
    <script src="https://www.gstatic.com/cast/sdk/libs/receiver/2.0.0/cast_receiver.js"></script>
    <!-- define style vars for team colors -->
    <style>
        :root {
            --away-primary: #1e88eA;
            --away-secondary: #ffffff;
            --home-primary: #43a047;
            --home-secondary: #fdd835;
            --fielding-primary: var(--away-primary);
            --fielding-secondary: var(--away-secondary);
            --batting-primary: var(--home-primary);
            --batting-secondary: var(--home-secondary);
        }
    </style>
    <title>Baseball Game Receiver</title>
</head>
<body>
<div id="app" data-game-id="{{ $game->id }}" data-ended="{{ $game->ended ? 'true' : 'false' }}" data-inning="{{ $game->inning }}" data-receiver="true">
</div>
@vite(['resources/js/app.js'])
<script>
// Receiver logic - just start the context, the Vue app will handle the rest
const context = cast.framework.CastReceiverContext.getInstance();
context.start();
</script>
</body>
</html></content>
<parameter name="filePath">/Users/kindred/dev/baseball-stats/resources/views/game/receiver.blade.php