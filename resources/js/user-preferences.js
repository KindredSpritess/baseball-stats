import './bootstrap';
import { createApp } from 'vue';
import UserPreferences from './components/UserPreferences.vue';

createApp(UserPreferences, {}).mount('#preferences-app');