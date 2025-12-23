import './bootstrap';
import { createApp } from 'vue';
import TouchScore from './components/TouchScore.vue';

const el = document.getElementById('app');
createApp(TouchScore, {
    gameId: Number(el.dataset.gameId),
    game: JSON.parse(el.dataset.game),
    state: JSON.parse(el.dataset.gameState),
}).mount('#app');