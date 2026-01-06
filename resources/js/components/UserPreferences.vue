<template>
  <div class="user-preferences">
    <h2>User Preferences</h2>
    <div v-if="flashMessage" class="flash-message" :class="flashMessage.type">
      {{ flashMessage.text }}
    </div>
    <form @submit.prevent="savePreferences">
      <div class="preference-item">
        <label>
          <input type="checkbox" v-model="preferences.simplifyTrajectories" />
          Simplify trajectories to Ground (G) and Fly (F) only
        </label>
      </div>
      <div class="preference-item">
        <label>
          <input type="checkbox" v-model="preferences.removeErrors" />
          Remove error options
        </label>
      </div>
      <div class="preference-item">
        <label>
          <input type="checkbox" v-model="preferences.removeAdvancedPitchTypes" />
          Remove advanced pitch types
        </label>
      </div>
      <div class="preference-item">
        <label>
          <input type="checkbox" v-model="preferences.removeIntentionalWalks" />
          Remove intentional walk options
        </label>
      </div>
      <div class="preference-item">
        <label>
          <input type="checkbox" v-model="preferences.removeAdvancementOptions" />
          Remove SB/CS/WP/PB from advancement options, just advance them
        </label>
      </div>
      <div class="preference-item">
        <label>
          <input type="checkbox" v-model="preferences.allowDropThirdStrikes" />
          Allow drop third strikes
        </label>
      </div>
      <div class="preference-item">
        <label>
          <input type="checkbox" v-model="preferences.removeBalks" />
          Remove balk options
        </label>
      </div>
      <div class="preference-item">
        <label>
          <input type="checkbox" v-model="preferences.balksCanCountTowardPitchCount" />
          Balks can count toward pitch count
        </label>
      </div>
      <div class="preference-item">
        <label>
          <input type="checkbox" v-model="preferences.lineupDefensiveChanges" />
          Use lineup for defensive changes
        </label>
      </div>
      <button type="submit" :disabled="saving">Save Preferences</button>
    </form>
  </div>
</template>

<script>
export default {
  name: 'UserPreferences',
  data() {
    return {
      preferences: {
        allowDropThirdStrikes: true,
        simplifyTrajectories: false,
        removeErrors: false,
        removeAdvancedPitchTypes: false,
        removeIntentionalWalks: false,
        removeAdvancementOptions: false,
        removeBalks: false,
        balksCanCountTowardPitchCount: false,
        lineupDefensiveChanges: false,
      },
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
        const response = await fetch('/api/user', {
          headers: {
            'Accept': 'application/json',
          },
        });
        const user = await response.json();
        if (user.preferences) {
          this.preferences = { ...this.preferences, ...user.preferences };
        }
      } catch (error) {
        console.error('Failed to load preferences:', error);
      }
    },
    async savePreferences() {
      this.saving = true;
      try {
        const response = await fetch('/api/user/preferences', {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({ preferences: this.preferences }),
        });
        if (response.ok) {
          this.showFlashMessage('Preferences saved successfully!', 'success');
        } else {
          this.showFlashMessage('Failed to save preferences.', 'error');
        }
      } catch (error) {
        console.error('Failed to save preferences:', error);
        this.showFlashMessage('Failed to save preferences.', 'error');
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
.user-preferences {
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