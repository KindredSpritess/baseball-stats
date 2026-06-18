import './bootstrap';
import { createApp } from 'vue';
import Preferences from './components/Preferences.vue';

const element = document.getElementById('preferences-app');
createApp(Preferences, {
  entityName: element.dataset.entityName,
  loadUrl: element.dataset.loadUrl,
  saveUrl: element.dataset.saveUrl,
  initialPreferences: element.dataset.initialPreferences ? JSON.parse(element.dataset.initialPreferences) : null,
  initialCompetitionDetails: element.dataset.initialCompetitionDetails ? JSON.parse(element.dataset.initialCompetitionDetails) : null,
}).mount('#preferences-app');