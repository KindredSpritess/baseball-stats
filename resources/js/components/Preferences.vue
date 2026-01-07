<template>
  <div class="preferences">
    <h2>{{ entityName }}</h2>
    <div v-if="flashMessage" class="flash-message" :class="flashMessage.type">
      {{ flashMessage.text }}
    </div>
    <form @submit.prevent="savePreferences">
      <div class="preference-item" v-if="'simplifyTrajectories' in preferences">
        <label>
          <input type="checkbox" v-model="preferences.simplifyTrajectories" />
          Simplify trajectories to Ground (G) and Fly (F) only
        </label>
      </div>
      <div class="preference-item" v-if="'removeErrors' in preferences">
        <label>
          <input type="checkbox" v-model="preferences.removeErrors" />
          Remove error options
        </label>
      </div>
      <div class="preference-item" v-if="'removeAdvancedPitchTypes' in preferences">
        <label>
          <input type="checkbox" v-model="preferences.removeAdvancedPitchTypes" />
          Remove advanced pitch types
        </label>
      </div>
      <div class="preference-item" v-if="'removeIntentionalWalks' in preferences">
        <label>
          <input type="checkbox" v-model="preferences.removeIntentionalWalks" />
          Remove intentional walk options
        </label>
      </div>
      <div class="preference-item" v-if="'removeAdvancementOptions' in preferences">
        <label>
          <input type="checkbox" v-model="preferences.removeAdvancementOptions" />
          Remove SB/CS/WP/PB from advancement options, just advance them
        </label>
      </div>
      <div class="preference-item" v-if="'allowDropThirdStrikes' in preferences">
        <label>
          <input type="checkbox" v-model="preferences.allowDropThirdStrikes" />
          Allow drop third strikes
        </label>
      </div>
      <div class="preference-item" v-if="'removeBalks' in preferences">
        <label>
          <input type="checkbox" v-model="preferences.removeBalks" />
          Remove balk options
        </label>
      </div>
      <div class="preference-item" v-if="'balksCanCountTowardPitchCount' in preferences">
        <label>
          <input type="checkbox" v-model="preferences.balksCanCountTowardPitchCount" />
          Balks can count toward pitch count
        </label>
      </div>
      <div class="preference-item" v-if="'lineupDefensiveChanges' in preferences">
        <label>
          <input type="checkbox" v-model="preferences.lineupDefensiveChanges" />
          Use lineup for defensive changes
        </label>
      </div>
      <button type="submit" :disabled="saving">Save {{ entityName }}</button>
    </form>
  </div>
</template>

<script>
export default {
  name: 'Preferences',
  props: {
    loadUrl: {
      type: String,
      default: '/api/user'
    },
    saveUrl: {
      type: String,
      default: '/api/user/preferences'
    },
    entityName: {
      type: String,
      default: 'Preferences'
    },
    initialPreferences: {
      type: Object,
      default: () => ({
        allowDropThirdStrikes: true,
        simplifyTrajectories: false,
        removeErrors: false,
        removeAdvancedPitchTypes: false,
        removeIntentionalWalks: false,
        removeAdvancementOptions: false,
        removeBalks: false,
        balksCanCountTowardPitchCount: false,
        lineupDefensiveChanges: false,
      })
    }
  },
  data() {
    return {
      preferences: { ...this.initialPreferences },
      flashMessage: null,
      saving: false,
      flashTimeout: null,
    };
  },
  mounted() {
    this.loadPreferences();
  },
  methods: {
    async loadPreferences() {
      try {
        const response = await fetch(this.loadUrl, {
          headers: {
            'Accept': 'application/json',
          },
        });
        const data = await response.json();
        if (data.preferences) {
          this.preferences = { ...this.initialPreferences, ...data.preferences };
        }
      } catch (error) {
        console.error('Failed to load preferences:', error);
      }
    },
    async savePreferences() {
      this.saving = true;
      console.log('Saving preferences to', this.saveUrl, this.preferences);
      try {
        const response = await fetch(this.saveUrl, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({ preferences: this.preferences }),
        });
        if (response.ok) {
          this.showFlashMessage(`${this.entityName} saved successfully!`, 'success');
        } else {
          this.showFlashMessage(`Failed to save ${this.entityName.toLowerCase()}.`, 'error');
        }
      } catch (error) {
        console.error('Failed to save preferences:', error);
        this.showFlashMessage(`Failed to save ${this.entityName.toLowerCase()}.`, 'error');
      } finally {
        this.saving = false;
      }
    },
    showFlashMessage(text, type) {
      this.flashMessage = { text, type };
      if (this.flashTimeout) {
        clearTimeout(this.flashTimeout);
      }
      this.flashTimeout = setTimeout(() => {
        this.flashMessage = null;
      }, 5000); // Clear after 5 seconds
    },
    getToken() {
      return window.Laravel.apiToken;
    },
  },
};
</script>

<style scoped>
.preferences {
  max-width: 600px;
  margin: 0 auto;
  padding: 20px;
}

.flash-message {
  padding: 10px;
  margin-bottom: 20px;
  border-radius: 4px;
  font-weight: bold;
}

.flash-message.success {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.flash-message.error {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.preference-item {
  margin-bottom: 15px;
}

.preference-item label {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.preference-item input {
  margin-right: 10px;
}

button {
  padding: 10px 20px;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

button:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}
</style>