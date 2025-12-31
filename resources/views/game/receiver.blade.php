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
    <script src="//www.gstatic.com/cast/sdk/libs/caf_receiver/v3/cast_receiver_framework.js"></script>
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
@vite(['resources/js/app.js'])
<script>
    // Receiver logic - just start the context, the Vue app will handle the rest
    const context = cast.framework.CastReceiverContext.getInstance();
    // We need to receive the game id from the sender, and load that component.
    // Including creating the app.
    context.addCustomMessageListener('urn:x-cast:app.statskeeper.game', (customEvent) => {
        const gameId = customEvent.data.gameId;
        console.log('Received game ID:', gameId);
        try {
            let el = document.getElementById('app');
            if (!el) {
                // Create the app div if it doesn't exist
                el = document.createElement('div');
                el.id = 'app';
                document.body.appendChild(el);
            }
            createApp(Game, {
                gameId: Number(gameId),
                ended: false,
                inning: 1,
                receiver: true,
            }).mount('#app');
        } catch (error) {
            // Send error back to sender
            console.error('Error initializing game receiver:', error);
            context.sendCustomMessage('urn:x-cast:app.statskeeper.error', null, { message: error.message });
        }
    });
    context.start();
</script>
</body>
</html>