<template>
  <div class="touch-score-container">
    <div class="game-header">
      <h2>{{ game.away_team.name }} @ {{ game.home_team.name }}</h2>
      <div class="score">{{ state.score.join(' - ') }}</div>
      <div class="inning">{{ state.inning }}{{ state.half ? '▼' : '▲' }}</div>
    </div>

    <div class="base-runners-container">
      <BaseRunnerActions ref="baseRunner0" :base="0" :game="game" :state="state" @log-play="logPlay" :preferences="preferences" />
      <BaseRunnerActions ref="baseRunner1" :base="1" :game="game" :state="state" @log-play="logPlay" :preferences="preferences" />
      <BaseRunnerActions ref="baseRunner2" :base="2" :game="game" :state="state" @log-play="logPlay" :preferences="preferences" />
    </div>

    <BatterActions @log-play="logPlay" :state="state" :runner-plays="runnerActions" :preferences="preferences" />

    <div class="status">
      <p v-if="lastResponse" :class="{ success: lastResponse.status === 'success', error: lastResponse.status === 'error' }">
        {{ lastResponse.status === 'success' ? 'Play logged successfully' : 'Error: ' + lastResponse.message }}
      </p>
    </div>
  </div>
</template>

<script>
import BaseRunnerActions from './BaseRunnerActions.vue';
import BatterActions from './BatterActions.vue';

export default {
  name: 'TouchScore',
  components: {
    BaseRunnerActions,
    BatterActions
  },
  props: {
    gameId: Number,
    game: Object,
    state: Object,
  },
  data() {
    return {
      lastResponse: null,
      currentGame: this.game,
      preferences: {}
    }
  },
  computed: {
    runnerActions() {
      return [
        this.$refs.baseRunner0 ? this.$refs.baseRunner0.play : '',
        this.$refs.baseRunner1 ? this.$refs.baseRunner1.play : '',
        this.$refs.baseRunner2 ? this.$refs.baseRunner2.play : '',
      ];
    }
  },
  mounted() {
    this.loadPreferences();
  },
  methods: {
    async loadPreferences() {
      try {
        const response = await fetch('/api/user', {
          headers: {
            'Authorization': `Bearer ${window.Laravel.apiToken}`,
            'Accept': 'application/json',
          },
        });
        const user = await response.json();
        this.preferences = user.preferences || {};
      } catch (error) {
        console.error('Failed to load preferences:', error);
      }
    },
    async logPlay(playCode) {
      await this.sendPlay(playCode);
    },
    async sendPlay(play) {
      try {
        const response = await fetch(`/game/${this.gameId}/log`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({ play })
        });
        const result = await response.json();
        this.lastResponse = result;
        if (result.status === 'success') {
          // Update game state
          this.updateGameState(result.state);
        }
      } catch (error) {
        this.lastResponse = { status: 'error', message: error.message };
      }
    },
    updateGameState(newState) {
      // Update the game object with new state
      this.currentGame.inning = newState.inning;
      this.currentGame.half = newState.half;
      this.currentGame.score = newState.score;
      this.currentGame.ended = newState.ended;
      // Force re-render
      this.$forceUpdate();
    }
  }
}
</script>

<style scoped>
.touch-score-container {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
  font-family: Arial, sans-serif;
}

.game-header {
  text-align: center;
  margin-bottom: 30px;
}

.game-header h2 {
  margin: 0 0 10px 0;
}

.score {
  font-size: 24px;
  font-weight: bold;
}

.inning {
  font-size: 18px;
}

.base-runners-container {
  display: flex;
  gap: 15px;
  margin-bottom: 30px;
}

.status {
  text-align: center;
  min-height: 30px;
}

.status p {
  margin: 0;
  padding: 10px;
  border-radius: 5px;
}

.status p.success {
  background-color: #d4edda;
  color: #155724;
}

.status p.error {
  background-color: #f8d7da;
  color: #721c24;
}
</style>