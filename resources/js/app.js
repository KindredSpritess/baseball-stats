import './bootstrap';
import { createApp } from 'vue';
import Game from './components/Game.vue';

const el = document.getElementById('app');
if (el) {
    createApp(Game, {
        gameId: Number(el.dataset.gameId),
        ended: el.dataset.ended === 'true',
        inning: parseInt(el.dataset.inning),
    }).mount('#app');
}