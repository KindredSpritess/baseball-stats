<template>
  <div class="user-preferences">
    <h2>User Preferences</h2>
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
      },
      saving: false,
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
            'Authorization': `Bearer ${this.getToken()}`,
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
            'Authorization': `Bearer ${this.getToken()}`,
            'Accept': 'application/json',
          },
          body: JSON.stringify({ preferences: this.preferences }),
        });
        if (response.ok) {
          alert('Preferences saved successfully!');
        } else {
          alert('Failed to save preferences.');
        }
      } catch (error) {
        console.error('Failed to save preferences:', error);
        alert('Failed to save preferences.');
      } finally {
        this.saving = false;
      }
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