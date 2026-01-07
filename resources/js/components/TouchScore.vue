<template>
  <div class="touch-score-container">
    <div class="game-header">
      <h2>{{ game.away_team.name }} @ {{ game.home_team.name }}</h2>
      <div class="score">{{ state.score.join(' - ') }}</div>
      <div class="inning">{{ state.inning }}{{ state.half ? '▼' : '▲' }} ({{ state.outs }} {{ state.outs == 1 ? 'out' : 'outs' }})</div>
      <button @click="showOptions = !showOptions" class="options-btn">⚙️ Options</button>
    </div>

    <div v-if="showOptions" class="options-menu">
      <div class="options-overlay" @click="showOptions = false"></div>
      <div class="options-content">
        <h3>Game Options</h3>
        <button @click="overrideCount" class="option-item">Override Count</button>
        <button @click="sideAway" class="option-item">Side Away</button>
        <button @click="defensiveChangesShow" class="option-item">Defensive Changes</button>
        <button @click="broadcastMessage" class="option-item">Broadcast Message</button>
        <button @click="endGame" class="option-item">End Game</button>
        <a :href="`/game/${gameId}`" style="text-decoration: none;">
          <button  class="option-item">Full Scoring</button>
        </a>
      </div>
    </div>

    <div v-if="showEndGameModal" class="end-game-modal">
      <div class="end-game-overlay" @click="cancelEndGame"></div>
      <div class="end-game-content">
        <h3>End Game</h3>
        <div class="final-score">
          <p>Final Score: {{ game.away_team.name }} {{ state.score[0] }} - {{ game.home_team.name }} {{ state.score[1] }}</p>
        </div>
        
        <div v-if="winningTeam !== null" class="pitchers-section">
          <div class="winning-pitcher">
            <h4>Winning Pitcher</h4>
            <div v-if="canSelectWinningPitcher" class="pitcher-selection">
              <select v-model="selectedWinningPitcher" class="pitcher-select">
                <option :value="null">Select winning pitcher...</option>
                <option v-for="pitcher in winningPitchers" :key="pitcher.id" :value="pitcher">
                  {{ pitcher.person.lastName }}, {{ pitcher.person.firstName }} ({{ pitcher.stats?.TO || 0 }} outs)
                </option>
              </select>
            </div>
            <div v-else class="pitcher-display">
              <p>{{ winningPitchers[0]?.person?.lastName || 'Unknown' }}, {{ winningPitchers[0]?.person?.firstName || '' }}</p>
            </div>
          </div>

          <div class="losing-pitcher">
            <h4>Losing Pitcher</h4>
            <p>{{ losingPitcher?.person?.lastName || 'Unknown' }}, {{ losingPitcher?.person?.firstName || '' }}</p>
          </div>
        </div>
        
        <div class="end-game-actions">
          <button @click="confirmEndGame" :disabled="canSelectWinningPitcher && !selectedWinningPitcher" class="confirm-btn">End Game</button>
          <button @click="cancelEndGame" class="cancel-btn">Cancel</button>
        </div>
      </div>
    </div>

    <div :style="{ display: showDefensiveChanges ? 'none' : null}">
      <BatterActions ref="batterActions" @log-play="logPlay" :game="game" :state="state" :runner-plays="runnerActions" :preferences="preferences" @reset-play="resetPlay" @force="forceOneBase" :errors="errors" @error="onError" />
    </div>

    <div class="base-runners-container" :style="{ display: showDefensiveChanges ? 'none' : null}">
      <BaseRunnerActions ref="baseRunner0" :base="0" :game="game" :state="state" :pitch="lastPitch" @log-play="logPlay" :preferences="preferences" :forced="isMounted && forced[0]" :errors="errors" @error="onError" />
      <BaseRunnerActions ref="baseRunner1" :base="1" :game="game" :state="state" :pitch="lastPitch" @log-play="logPlay" :preferences="preferences" :forced="isMounted && forced[1]" :errors="errors" @error="onError" />
      <BaseRunnerActions ref="baseRunner2" :base="2" :game="game" :state="state" :pitch="lastPitch" @log-play="logPlay" :preferences="preferences" :forced="isMounted && forced[2]" :errors="errors" @error="onError" />
    </div>

    <DefensiveChanges
      v-if="showDefensiveChanges && !preferences.lineupDefensiveChanges"
      :game="game"
      :state="state"
      @defensive-change="handleDefensiveChange"
      @close="showDefensiveChanges = false"
    />

    <LineupChanges
      v-if="showDefensiveChanges && preferences.lineupDefensiveChanges"
      :game="game"
      :state="state"
      :preferences="preferences"
      @log-play="logPlay"
      @close="showDefensiveChanges = false"
    />

    <div class="status">
      <p v-if="lastResponse" :class="{ success: lastResponse.status === 'success', error: lastResponse.status === 'error' }">
        {{ lastResponse.status === 'success' ? (lastResponse.play.human || lastResponse.play.game_event) : 'Error: ' + lastResponse.message }}
        <button v-if="lastResponse.status === 'success'" @click="undoLastPlay">Undo</button>
      </p>
    </div>
  </div>
</template>

<script>
import BaseRunnerActions from './BaseRunnerActions.vue';
import BatterActions from './BatterActions.vue';
import DefensiveChanges from './DefensiveChanges.vue';
import LineupChanges from './LineupChanges.vue';

export default {
  name: 'TouchScore',
  components: {
    BaseRunnerActions,
    BatterActions,
    DefensiveChanges,
    LineupChanges,
  },
  props: {
    gameId: Number,
    game: Object,
    initialState: Object,
    lastPlay: Object,
  },
  data() {
    return {
      lastResponse: { status: 'success', play: this.lastPlay },
      currentGame: this.game,
      state: {...this.initialState},
      preferences: {},
      isMounted: false,
      showOptions: false,
      showDefensiveChanges: false,
      showEndGameModal: false,
      selectedWinningPitcher: null,
      errors: [],
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
      if (!this.isMounted || !this.$refs.batterActions) return null;
      let lp = this.$refs.batterActions.pitchSequence.slice(-1);
      if (['CI', 'HBP'].includes(this.$refs.batterActions.plays[0])) {
        return 'f'; // these kill the play like a foul.
      }
      return lp;
    },
    forced() {
      if (!this.isMounted) return {};
      const occupied = [];
      const forced = {};
      // Include the batters base if he's not at home and he's not out.
      let bb = this.$refs.batterActions?.base ?? 0;
      if (bb > 0) occupied.push(bb - 1);
      for (let i = 0; i < 3; i++) {
        const rb = this.$refs[`baseRunner${i}`]?.nextBase;
        if (rb >= 0) {
          forced[i] = occupied.includes(rb);
          occupied.push(rb);
        }
      }

      return forced;
    },
    winningTeam() {
      const awayScore = this.state.score[0];
      const homeScore = this.state.score[1];
      if (awayScore > homeScore) return 0; // away
      if (homeScore > awayScore) return 1; // home
      return null; // tie
    },
    losingTeam() {
      const awayScore = this.state.score[0];
      const homeScore = this.state.score[1];
      if (awayScore < homeScore) return 0; // away
      if (homeScore < awayScore) return 1; // home
      return null; // tie
    },
    winningPitchers() {
      if (this.winningTeam === null) return [];
      const pitchers = (this.state.pitchers[this.winningTeam] || []).map(playerId => {
        return this.game.players.find(p => p.id === playerId);
      }).filter(p => p);

      return pitchers;
    },
    winningPitcher() {
      if (this.winningTeam === null) return null;
      return this.game.players.find(p => p.id === this.state.pitchersOfRecord.winning) ?? null;
    },
    losingPitcher() {
      if (this.losingTeam === null) return null;
      console.log('Losing pitcher ID:', this.game.players.find(p => p.id === this.state.pitchersOfRecord.losing));
      return this.game.players.find(p => p.id === this.state.pitchersOfRecord.losing) ?? null;
    },
    canSelectWinningPitcher() {
      if (!this.winningPitcher) return false;
      const wp = this.winningPitcher;
      return wp && this.winningPitchers[0].id === wp.id && (wp.stats?.TO || 0) < 15;
    },
  },
  mounted() {
    this.loadPreferences();
    this.isMounted = true;
  },
  methods: {
    async loadPreferences() {
      try {
        const response = await fetch(`/api/game/${this.gameId}/preferences`, {
          headers: {
            'Accept': 'application/json',
          },
        });
        const preferences = await response.json();
        this.preferences = preferences || {};
      } catch (error) {
        console.error('Failed to load preferences:', error);
      }
    },
    resetPlay() {
      if (this.$refs.baseRunner0) {
        this.$refs.baseRunner0.reset();
      }
      if (this.$refs.baseRunner1) {
        this.$refs.baseRunner1.reset();
      }
      if (this.$refs.baseRunner2) {
        this.$refs.baseRunner2.reset();
      }
      this.errors = [];
    },
    async logPlay(...playCodes) {
      for (const playCode of playCodes) {
        if (!playCode) continue;
        await this.sendPlay(playCode);
      }
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
          this.updateStats(result.stats);
        }
      } catch (error) {
        this.lastResponse = { status: 'error', message: error.message };
      }
    },
    async undoLastPlay() {
      if (!confirm('Are you sure you want to undo the last play?')) {
        return;
      }
      try {
        const response = await fetch(`/game/${this.gameId}/undo`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
        });
        const result = await response.json();
        this.lastResponse = result;
        if (result.status === 'success') {
          // Update game state
          this.updateGameState(result.state);
          this.updateStats(result.stats);
        }
      } catch (error) {
        this.lastResponse = { status: 'error', message: error.message };
      }
    },
    updateGameState(newState) {
      // Update the game object with new state
      this.errors = [];
      this.$refs.batterActions.resetAtBat();
      this.$refs.baseRunner0.reset();
      this.$refs.baseRunner1.reset();
      this.$refs.baseRunner2.reset();
      this.state = newState;
      // Force re-render
      this.$forceUpdate();
    },
    updateStats(newStats) {
      // Update player stats in the game object
      for (const {player_id, stat, value} of newStats) {
        const player = this.game.players.find(p => p.id == player_id);
        if (player) {
          player.stats[stat] = value;
        }
      }
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
    defensiveChangesShow() {
      // this.showDefensiveChanges = true;
      this.showDefensiveChanges = true;
      this.showOptions = false;
    },
    handleDefensiveChange(command) {
      this.sendPlay(command);
    },
    endGame() {
      this.selectedWinningPitcher = this.canSelectWinningPitcher ? null : this.winningPitcher;
      this.showEndGameModal = true;
      this.showOptions = false;
    },
    confirmEndGame() {
      let play = 'Game Over';
      if (this.canSelectWinningPitcher && this.selectedWinningPitcher) {
        const index = this.winningPitchers.findIndex(p => p.id === this.selectedWinningPitcher.id) + 1;
        play += ` #${index}`;
      }
      this.sendPlay(play);
      this.showEndGameModal = false;
    },
    cancelEndGame() {
      this.showEndGameModal = false;
    },
    // A couple of events force runnners to move, when forced.
    forceOneBase() {
      console.log('Forcing runners to advance one base');
      if (!this.state.bases[0]) {
        return;
      }
      this.$refs.baseRunner0.logRunnerAction(2, '');
      if (!this.state.bases[1]) {
        return;
      }
      this.$refs.baseRunner1.logRunnerAction(3, '');
      this.state.bases[2] && this.$refs.baseRunner2.logRunnerAction(4, '');
    },
    onError(error) {
      this.errors.push(error);
    },
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

.end-game-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1001;
}

.end-game-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
}

.end-game-content {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  min-width: 400px;
  max-width: 90vw;
}

.end-game-content h3 {
  margin: 0 0 20px 0;
  text-align: center;
}

.final-score {
  text-align: center;
  margin-bottom: 20px;
  font-size: 18px;
  font-weight: bold;
}

.pitchers-section {
  margin-bottom: 20px;
}

.winning-pitcher, .losing-pitcher {
  margin-bottom: 15px;
}

.winning-pitcher h4, .losing-pitcher h4 {
  margin: 0 0 10px 0;
  font-size: 16px;
}

.pitcher-selection {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.pitcher-select {
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.pitcher-display p {
  margin: 0;
  padding: 8px;
  background-color: #f5f5f5;
  border-radius: 4px;
  font-size: 14px;
}

.end-game-actions {
  display: flex;
  gap: 10px;
  justify-content: center;
}

.confirm-btn, .cancel-btn {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.confirm-btn {
  background-color: #28a745;
  color: white;
}

.confirm-btn:disabled {
  background-color: #6c757d;
  cursor: not-allowed;
}

.confirm-btn:hover:not(:disabled) {
  background-color: #218838;
}

.cancel-btn {
  background-color: #6c757d;
  color: white;
}

.cancel-btn:hover {
  background-color: #5a6268;
}
</style>