<template>
  <div class="touch-score-container">
    <div class="game-header">
      <h2>{{ game.away_team.name }} @ {{ game.home_team.name }}</h2>
      <div class="score">{{ state.score.join(' - ') }}</div>
      <div class="inning">{{ state.inning }}{{ state.half ? '▼' : '▲' }}</div>
      <button @click="showOptions = !showOptions" class="options-btn">⚙️ Options</button>
    </div>

    <div v-if="showOptions" class="options-menu">
      <div class="options-overlay" @click="showOptions = false"></div>
      <div class="options-content">
        <h3>Game Options</h3>
        <button @click="overrideCount" class="option-item">Override Count</button>
        <button @click="sideAway" class="option-item">Side Away</button>
        <button @click="broadcastMessage" class="option-item">Broadcast Message</button>
        <button @click="endGame" class="option-item">End Game</button>
      </div>
    </div>

    <div class="base-runners-container">
      <BaseRunnerActions ref="baseRunner0" :base="0" :game="game" :state="state" :pitch="lastPitch" @log-play="logPlay" :preferences="preferences" />
      <BaseRunnerActions ref="baseRunner1" :base="1" :game="game" :state="state" :pitch="lastPitch" @log-play="logPlay" :preferences="preferences" />
      <BaseRunnerActions ref="baseRunner2" :base="2" :game="game" :state="state" :pitch="lastPitch" @log-play="logPlay" :preferences="preferences" />
    </div>

    <BatterActions ref="batterActions" @log-play="logPlay" :game="game" :state="state" :runner-plays="runnerActions" :preferences="preferences" />

    <div class="status">
      <p v-if="lastResponse" :class="{ success: lastResponse.status === 'success', error: lastResponse.status === 'error' }">
        {{ lastResponse.status === 'success' ? lastResponse.playLog : 'Error: ' + lastResponse.message }}
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
    initialState: Object,
  },
  data() {
    return {
      lastResponse: null,
      currentGame: this.game,
      state: {...this.initialState},
      preferences: {},
      isMounted: false,
      showOptions: false,
    }
  },
  computed: {
    runnerActions() {
      if (!this.isMounted) return [];
      return [
        this.$refs.baseRunner0 ? this.$refs.baseRunner0.play : '',
        this.$refs.baseRunner1 ? this.$refs.baseRunner1.play : '',
        this.$refs.baseRunner2 ? this.$refs.baseRunner2.play : '',
      ];
    },
    lastPitch() {
      if (!this.isMounted) return null;
      return this.$refs.batterActions ? this.$refs.batterActions.pitchSequence.slice(-1) : null;
    },
  },
  mounted() {
    this.loadPreferences();
    this.isMounted = true;
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
      this.$refs.batterActions.resetAtBat();
      this.$refs.baseRunner0.reset();
      this.$refs.baseRunner1.reset();
      this.$refs.baseRunner2.reset();
      this.state = newState;
      // Force re-render
      this.$forceUpdate();
    },
    overrideCount() {
      const count = prompt('Enter new count (balls-strikes):', `${this.state.balls}-${this.state.strikes}`);
      if (count && count.match(/^\d-\d$/)) {
        const [balls, strikes] = count.split('-').map(Number);
        this.sendPlay(`SC ${balls}-${strikes}`);
        this.showOptions = false;
      }
    },
    sideAway() {
      if (confirm('Are you sure you want to call "Side Away"?')) {
        this.sendPlay('Side Away');
        this.showOptions = false;
      }
    },
    broadcastMessage() {
      const message = prompt('Enter broadcast message:');
      if (message && message.trim()) {
        this.sendPlay(`! ${message.trim()}`);
        this.showOptions = false;
      }
    },
    endGame() {
      if (confirm('Are you sure you want to end the game?')) {
        this.sendPlay('Game Over');
        this.showOptions = false;
      }
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
  position: relative;
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

.options-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  padding: 5px;
}

.options-menu {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1000;
}

.options-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
}

.options-content {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  min-width: 250px;
}

.options-content h3 {
  margin: 0 0 15px 0;
  text-align: center;
}

.option-item {
  display: block;
  width: 100%;
  padding: 10px;
  margin: 5px 0;
  border: 1px solid #ddd;
  background: #f9f9f9;
  cursor: pointer;
  border-radius: 4px;
  text-align: center;
}

.option-item:hover {
  background: #e9e9e9;
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