import './bootstrap';
import { createApp } from 'vue';
import Game from './components/Game.vue';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wsPath: import.meta.env.VITE_REVERB_PATH,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
});

const el = document.getElementById('app');
if (el) {
    createApp(Game, {
        gameId: Number(el.dataset.gameId),
        ended: el.dataset.ended === 'true',
        inning: parseInt(el.dataset.inning),
    }).mount('#app');
} else {
    window.Game = Game;
    window.createApp = createApp;
}