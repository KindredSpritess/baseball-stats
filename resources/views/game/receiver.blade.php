<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/styles.css" />
    <link rel="stylesheet" href="/game.css" />
    <script src="//www.gstatic.com/cast/sdk/libs/caf_receiver/v3/cast_receiver_framework.js"></script>
    <script src="//www.gstatic.com/cast/sdk/libs/devtools/debug_layer/caf_receiver_logger.js"></script>
    <script>
        window.addEventListener('unhandledrejection', event => {
            // Display the error message on the page for easier debugging
            const errorMessage = document.createElement('div');
            errorMessage.style.position = 'absolute';
            errorMessage.style.top = '0';
            errorMessage.style.left = '0';
            errorMessage.style.backgroundColor = 'red';
            errorMessage.style.color = 'white';
            errorMessage.style.padding = '1rem';
            errorMessage.style.zIndex = '1000';
            errorMessage.textContent = 'Unhandled Promise Rejection: ' + event.reason;
            document.body.appendChild(errorMessage);
        });
    </script>
    <style>
        :root {
            --away-primary: #1e88eA;
            --away-secondary: #ffffff;
            --home-primary: #43a047;
            --home-secondary: #fdd835;
        }
    </style>
    <title>Baseball Game Receiver</title>
</head>
<body>
<div id="receiver">
    <!-- Display a loading message until the game is loaded -->
    <p>Loading game...</p>
</div>
@vite(['resources/js/app.js'])
<script>
    const castDebugLogger = cast.debug.CastDebugLogger.getInstance();
    const context = cast.framework.CastReceiverContext.getInstance();
    context.addEventListener(cast.framework.system.EventType.READY, () => {
        if (!castDebugLogger.debugOverlayElement_) {
            // Enable debug logger and show a 'DEBUG MODE' overlay at top left corner.
            castDebugLogger.setEnabled(true);
            // Show debug overlay
            castDebugLogger.showDebugLogs(true);
            // Clear log messages on debug overlay
            castDebugLogger.clearDebugLogs();
        }
    });

    window.onerror = function (message, source, lineno, colno, error) {
        castDebugLogger.error('GLOBAL ERROR:', {
            message,
            source,
            lineno,
            colno,
            stack: error?.stack
        });
    };

    window.addEventListener('unhandledrejection', event => {
        castDebugLogger.error('UNHANDLED PROMISE:', event.reason);
    });

    // Receiver logic - just start the context, the Vue app will handle the rest
    // We need to receive the game id from the sender, and load that component.
    // Including creating the app.
    context.addCustomMessageListener('urn:x-cast:app.statskeeper.game', (customEvent) => {
        const gameId = customEvent.data.gameId;
        castDebugLogger.info('Received game ID:', gameId);
        try {
            let el = document.getElementById('receiver');
            castDebugLogger.info('VUE', 'Initializing Vue app for game ID: ' + JSON.stringify([el, createApp]));

            createApp(Game, {
                gameId: Number(gameId),
                isReceiver: true,
            }).mount('#receiver');
        } catch (error) {
            // Send error back to sender
            castDebugLogger.error('VUE', 'Error initializing game receiver: ' + JSON.stringify(error));
        }
    });
    context.start();

    // Add debug logging for remote button presses
    window.onkeydown = function(event) {
        castDebugLogger.info('REMOTE KEYDOWN:', {
            key: event.key,
            code: event.code,
            keyCode: event.keyCode
        });
    };
</script>

</body>
</html>